<?php
// Set the language attribute for the <html> tag on the home page.
$lang = isset($lang) ? $lang : 'fr';

// SEO metadata: Title and meta tags for the home page.
$metadata = isset($metadata) ? $metadata : '
<title>Vue d\'ensemble — Sans Suite</title>
<meta name="description" content="Suivez simplement vos candidatures, relances et entretiens en local.">
';

// Include this page in the sitemap for SEO purposes.
$addViewInSitemap = isset($addViewInSitemap) ? $addViewInSitemap : true;

// Set sitemap priority for this page (0.0 - lowest, 1.0 - highest).
$sitemapPriority = isset($sitemapPriority) ? $sitemapPriority : 0.8;
