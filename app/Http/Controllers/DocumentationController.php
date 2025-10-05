<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class DocumentationController extends Controller
{
    private $converter;

    private $tocGenerator;

    public function __construct()
    {
        $environment = new Environment([
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'insert' => 'after',
                'min_heading_level' => 1,
                'max_heading_level' => 6,
                'title' => 'Permalink',
                'symbol' => '#',
            ],
            'table_of_contents' => [
                'html_class' => 'table-of-contents',
                'position' => 'top',
                'style' => 'bullet',
                'min_heading_level' => 1,
                'max_heading_level' => 6,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new TableOfContentsExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    public function index()
    {
        return $this->show('getting-started', 'index');
    }

    public function show($category, $page = null)
    {
        // If no page specified, try to find an index.md file
        if (! $page) {
            $page = 'index';
        }

        $filePath = resource_path("docs/{$category}/{$page}.md");

        if (! File::exists($filePath)) {
            abort(404, 'Documentation page not found');
        }

        $markdown = File::get($filePath);
        $html = $this->converter->convert($markdown);

        // Extract table of contents from the HTML
        $toc = $this->extractTableOfContents($html);

        // Get all available documentation pages
        $navigation = $this->getNavigationStructure();

        return view('documentation', [
            'content' => $html,
            'toc' => $toc,
            'navigation' => $navigation,
            'currentCategory' => $category,
            'currentPage' => $page,
            'title' => $this->extractTitle($markdown) ?: ucfirst(str_replace('-', ' ', $page)),
        ]);
    }

    private function getNavigationStructure()
    {
        $docsPath = resource_path('docs');
        $navigation = [];

        if (! File::isDirectory($docsPath)) {
            return $navigation;
        }

        $categories = File::directories($docsPath);

        foreach ($categories as $categoryPath) {
            $categoryName = basename($categoryPath);
            $categoryFiles = File::files($categoryPath);

            $pages = [];
            foreach ($categoryFiles as $file) {
                if ($file->getExtension() === 'md') {
                    $pageName = $file->getFilenameWithoutExtension();
                    $pages[] = [
                        'name' => $pageName,
                        'title' => $this->getPageTitle($file->getPathname()),
                        'url' => route('documentation.show', [$categoryName, $pageName]),
                    ];
                }
            }

            // Sort pages, putting index first
            usort($pages, function ($a, $b) {
                if ($a['name'] === 'index') {
                    return -1;
                }
                if ($b['name'] === 'index') {
                    return 1;
                }

                return strcmp($a['name'], $b['name']);
            });

            $navigation[] = [
                'name' => $categoryName,
                'title' => ucfirst(str_replace('-', ' ', $categoryName)),
                'pages' => $pages,
            ];
        }

        return $navigation;
    }

    private function getPageTitle($filePath)
    {
        $content = File::get($filePath);

        return $this->extractTitle($content) ?: ucfirst(basename($filePath, '.md'));
    }

    private function extractTitle($markdown)
    {
        // Extract the first heading from markdown
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractTableOfContents($html)
    {
        // Extract headings from the HTML to create a table of contents
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new \DOMXPath($dom);

        $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
        $toc = [];

        foreach ($headings as $heading) {
            $level = (int) substr($heading->nodeName, 1);
            $text = trim($heading->textContent);
            
            // Remove the # symbol from the text if it exists
            $text = str_replace('#', '', $text);
            $text = trim($text);
            
            // Generate ID from the clean text
            $id = $this->generateId($text);

            $toc[] = [
                'level' => $level,
                'text' => $text,
                'id' => $id,
            ];
        }

        return $toc;
    }

    private function generateId($text)
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
    }
}
