#!/usr/bin/env node

import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const args = process.argv.slice(2);
const sourceIndex = args.indexOf('--source');
const outputIndex = args.indexOf('--output');

if (sourceIndex === -1 || outputIndex === -1 || ! args[sourceIndex + 1] || ! args[outputIndex + 1]) {
  throw new Error('Usage: node scripts/extract-lyli-handoff.mjs --source <web_lyli> --output <content.json>');
}

const sourceRoot = path.resolve(args[sourceIndex + 1]);
const publicRoot = path.join(sourceRoot, 'public');
const outputFile = path.resolve(args[outputIndex + 1]);
const requireFromSource = createRequire(path.join(sourceRoot, 'package.json'));
const ts = requireFromSource('typescript');
const moduleCache = new Map();

function resolveTsModule(request, parentFile) {
  let target;
  if (request.startsWith('@/')) {
    target = path.join(sourceRoot, request.slice(2));
  } else if (request.startsWith('.')) {
    target = path.resolve(path.dirname(parentFile), request);
  } else {
    return null;
  }

  for (const candidate of [target, `${target}.ts`, `${target}.tsx`, path.join(target, 'index.ts')]) {
    if (fs.existsSync(candidate)) {
      return candidate;
    }
  }

  throw new Error(`Cannot resolve ${request} from ${parentFile}`);
}

function loadTsModule(relativeFile) {
  const absoluteFile = path.resolve(sourceRoot, relativeFile);
  if (moduleCache.has(absoluteFile)) {
    return moduleCache.get(absoluteFile).exports;
  }

  const module = { exports: {} };
  moduleCache.set(absoluteFile, module);
  const source = fs.readFileSync(absoluteFile, 'utf8');
  const compiled = ts.transpileModule(source, {
    compilerOptions: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2020,
      esModuleInterop: true,
      jsx: ts.JsxEmit.ReactJSX,
    },
    fileName: absoluteFile,
  }).outputText;

  const localRequire = (request) => {
    const resolved = resolveTsModule(request, absoluteFile);
    return resolved ? loadTsModule(path.relative(sourceRoot, resolved)) : requireFromSource(request);
  };

  const execute = new Function('require', 'module', 'exports', '__filename', '__dirname', compiled);
  execute(localRequire, module, module.exports, absoluteFile, path.dirname(absoluteFile));
  return module.exports;
}

function findVariableInitializer(relativeFile, variableName) {
  const absoluteFile = path.join(sourceRoot, relativeFile);
  const source = fs.readFileSync(absoluteFile, 'utf8');
  const sourceFile = ts.createSourceFile(absoluteFile, source, ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
  let initializer = null;

  function visit(node) {
    if (ts.isVariableDeclaration(node) && ts.isIdentifier(node.name) && node.name.text === variableName) {
      initializer = node.initializer;
      return;
    }
    ts.forEachChild(node, visit);
  }

  visit(sourceFile);
  if (! initializer) {
    throw new Error(`Variable ${variableName} not found in ${relativeFile}`);
  }

  return { absoluteFile, sourceFile, initializer };
}

function evaluateVariable(relativeFile, variableName, context = {}) {
  const { absoluteFile, sourceFile, initializer } = findVariableInitializer(relativeFile, variableName);
  const expression = initializer.getText(sourceFile);
  const compiled = ts.transpileModule(`module.exports = ${expression};`, {
    compilerOptions: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2020,
      jsx: ts.JsxEmit.ReactJSX,
    },
    fileName: absoluteFile,
  }).outputText;
  const module = { exports: {} };
  const names = Object.keys(context);
  const values = Object.values(context);
  const execute = new Function('module', 'exports', ...names, compiled);
  execute(module, module.exports, ...values);
  return module.exports;
}

function extractMetadata(relativeFile) {
  const { initializer, sourceFile } = findVariableInitializer(relativeFile, 'metadata');
  if (! ts.isCallExpression(initializer) || initializer.arguments.length === 0) {
    return {};
  }
  const object = initializer.arguments[0];
  if (! ts.isObjectLiteralExpression(object)) {
    return {};
  }

  const metadata = {};
  for (const property of object.properties) {
    if (! ts.isPropertyAssignment(property)) {
      continue;
    }
    const key = property.name.getText(sourceFile).replace(/^['"]|['"]$/g, '');
    if (ts.isStringLiteralLike(property.initializer)) {
      metadata[key] = property.initializer.text;
    }
  }
  return metadata;
}

function jsxTagName(node) {
  const opening = ts.isJsxElement(node) ? node.openingElement : node;
  return opening.tagName.getText();
}

function jsxText(node) {
  if (ts.isJsxText(node)) {
    return node.text;
  }
  if (ts.isJsxExpression(node)) {
    if (! node.expression) {
      return '';
    }
    if (ts.isStringLiteralLike(node.expression)) {
      return node.expression.text;
    }
    if (node.expression.getText().includes('getFullYear')) {
      return String(new Date().getFullYear());
    }
    return '';
  }
  return node.getChildren().map(jsxText).join(' ');
}

function cleanText(value) {
  return value.replace(/\s+/g, ' ').replace(/\s+([.,:;!?])/g, '$1').trim();
}

function extractLegalPage(relativeFile) {
  const absoluteFile = path.join(sourceRoot, relativeFile);
  const source = fs.readFileSync(absoluteFile, 'utf8');
  const sourceFile = ts.createSourceFile(absoluteFile, source, ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
  const blocks = [];
  let inArticle = false;

  function visit(node) {
    if (ts.isJsxElement(node) && jsxTagName(node) === 'article') {
      const previous = inArticle;
      inArticle = true;
      node.children.forEach(visit);
      inArticle = previous;
      return;
    }
    if (! inArticle) {
      ts.forEachChild(node, visit);
      return;
    }
    if (ts.isJsxElement(node)) {
      const tag = jsxTagName(node);
      if (['h1', 'h2', 'p'].includes(tag)) {
        const text = cleanText(jsxText(node));
        if (text) {
          blocks.push({ type: tag, text });
        }
        return;
      }
      if (tag === 'ul' || tag === 'ol') {
        const items = [];
        function collect(itemNode) {
          if (ts.isJsxElement(itemNode) && jsxTagName(itemNode) === 'li') {
            items.push(cleanText(jsxText(itemNode)));
            return;
          }
          ts.forEachChild(itemNode, collect);
        }
        node.children.forEach(collect);
        blocks.push({ type: 'list', ordered: tag === 'ol', items });
        return;
      }
      node.children.forEach(visit);
      return;
    }
    ts.forEachChild(node, visit);
  }

  visit(sourceFile);
  return { metadata: extractMetadata(relativeFile), blocks };
}

function walk(directory) {
  const files = [];
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      files.push(...walk(absolute));
    } else if (entry.isFile()) {
      files.push(absolute);
    }
  }
  return files;
}

function collectImageMetadata(value, result = new Map()) {
  if (Array.isArray(value)) {
    value.forEach((item) => collectImageMetadata(item, result));
    return result;
  }
  if (! value || typeof value !== 'object') {
    return result;
  }
  if (typeof value.src === 'string' && value.src.startsWith('/') && typeof value.alt === 'string') {
    if (! result.has(value.src)) {
      result.set(value.src, {
        alt: value.alt,
        width: Number(value.width || 0),
        height: Number(value.height || 0),
      });
    }
  }
  Object.values(value).forEach((item) => collectImageMetadata(item, result));
  return result;
}

const { SITE } = loadTsModule('lib/site.ts');
const { PRODUCTS } = loadTsModule('lib/products.ts');
const { BLOG_POSTS } = loadTsModule('lib/blog.ts');
const { FAQ_ITEMS } = loadTsModule('lib/faq.ts');
const { HOMEPAGE_ABOUT } = loadTsModule('lib/homepage-about.ts');
const { HOMEPAGE_CONTACT } = loadTsModule('lib/homepage-contact.ts');
const { ACTIVE_PROMOTION } = loadTsModule('lib/promotions.ts');
const { GALLERY_IMAGES } = loadTsModule('lib/gallery.ts');

const siteContent = {
  name: SITE.name,
  domain: SITE.domain,
  url: SITE.url,
  locale: SITE.locale,
  title: SITE.title,
  brandDescription: SITE.brandDescription,
  description: SITE.description,
  keywords: SITE.keywords,
  ogImage: SITE.ogImage,
  twitterImage: SITE.twitterImage,
  logo: SITE.logo,
  phone: SITE.phone,
  email: SITE.email,
  socials: SITE.socials,
};

const homepage = {
  categories: evaluateVariable('app/page.tsx', 'CATEGORY_ITEMS'),
  categoryImages: evaluateVariable('app/page.tsx', 'CATEGORY_IMAGES'),
  images: evaluateVariable('app/page.tsx', 'HOMEPAGE_IMAGES'),
  featuredArticleImages: evaluateVariable('app/page.tsx', 'FEATURED_ARTICLE_IMAGES'),
  reviews: evaluateVariable('app/page.tsx', 'REVIEWS'),
  featuredArticles: evaluateVariable('app/page.tsx', 'FEATURED_ARTICLES', { BLOG_POSTS }),
};

const navigation = evaluateVariable('components/Navbar.tsx', 'NAV_LINKS').map(({ label, href }) => ({ label, href }));
const footer = {
  brandDescription: evaluateVariable('components/Footer.tsx', 'FOOTER_BRAND_DESCRIPTION'),
  navigation: evaluateVariable('components/Footer.tsx', 'NAVIGATION_LINKS'),
  categories: evaluateVariable('components/Footer.tsx', 'CATEGORY_LINKS'),
  policies: evaluateVariable('components/Footer.tsx', 'POLICY_LINKS'),
};

const content = {
  schemaVersion: 1,
  source: 'C:/Users/ADMIN/Music/web_lyli',
  site: siteContent,
  products: PRODUCTS,
  blogPosts: BLOG_POSTS,
  sharedFaq: FAQ_ITEMS,
  homepageAbout: HOMEPAGE_ABOUT,
  homepageContact: HOMEPAGE_CONTACT,
  promotion: ACTIVE_PROMOTION,
  gallery: GALLERY_IMAGES,
  homepage,
  navigation,
  footer,
  legal: {
    privacy: extractLegalPage('app/privacy/page.tsx'),
    terms: extractLegalPage('app/terms/page.tsx'),
  },
};

const imageMetadata = collectImageMetadata(content);
const assetDirectories = [
  'images/blog',
  'images/categories',
  'images/homepage',
  'images/og',
  'images/products',
  'gallery',
  'product-assets',
];
const rootAssets = [
  'apple-touch-icon-lylishop-v3.png',
  'og.png',
  'twitter-card.png',
  'logo.svg',
  'logo-lylishop.svg',
  'logo-lylishop-v2.svg',
  'logo-lylishop-v3.svg',
];
const assetFiles = [
  ...assetDirectories.flatMap((relative) => walk(path.join(publicRoot, relative))),
  ...rootAssets.map((relative) => path.join(publicRoot, relative)),
];

content.assets = assetFiles
  .map((absolute) => {
    const relative = path.relative(publicRoot, absolute).replaceAll(path.sep, '/');
    const sourcePath = `/${relative}`;
    const bytes = fs.readFileSync(absolute);
    return {
      sourcePath,
      bytes: bytes.length,
      sha256: crypto.createHash('sha256').update(bytes).digest('hex'),
      ...(imageMetadata.get(sourcePath) || {}),
    };
  })
  .sort((a, b) => a.sourcePath.localeCompare(b.sourcePath));

for (const [sourcePath] of imageMetadata) {
  if (! content.assets.some((asset) => asset.sourcePath === sourcePath)) {
    throw new Error(`Referenced asset is missing from transfer set: ${sourcePath}`);
  }
}

if (content.products.length !== 9 || content.blogPosts.length !== 5 || content.assets.length !== 63) {
  throw new Error(`Unexpected transfer counts: products=${content.products.length}, blog=${content.blogPosts.length}, assets=${content.assets.length}`);
}

fs.mkdirSync(path.dirname(outputFile), { recursive: true });
fs.writeFileSync(outputFile, `${JSON.stringify(content, null, 2)}\n`, 'utf8');
console.log(`Extracted products=${content.products.length} blog=${content.blogPosts.length} assets=${content.assets.length}`);
console.log(`Wrote ${outputFile}`);
