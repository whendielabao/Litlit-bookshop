<?php
/**
 * Smart-Sync API Proxy
 * Queries Open Library (and falls back to Google Books) for book metadata.
 * Returns JSON: { title, author, author_bio, description, cover_url, isbn, publisher, year }
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$type  = trim($_GET['type'] ?? 'title'); // 'title' or 'isbn'

if (empty($query)) {
    echo json_encode(['error' => 'No query provided']);
    exit();
}

function fetchUrl($url) {
    $ctx = stream_context_create(['http' => [
        'timeout'       => 8,
        'user_agent'    => 'BookshopInventory/1.0',
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

function cleanText($str, $limit = 500) {
    if (!$str) return '';
    $str = is_array($str) ? implode(' ', $str) : (string)$str;
    $str = preg_replace('/\s+/', ' ', strip_tags($str));
    return mb_substr(trim($str), 0, $limit);
}

$result = [
    'title'       => '',
    'author'      => '',
    'author_bio'  => '',
    'description' => '',
    'cover_url'   => '',
    'isbn'        => '',
    'publisher'   => '',
    'year'        => '',
];

/* ── 1. Open Library Search ───────────────────────────── */
if ($type === 'isbn') {
    $isbn = preg_replace('/[^0-9X]/', '', strtoupper($query));
    $olUrl = "https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&jscmd=data&format=json";
    $data  = fetchUrl($olUrl);

    if ($data && isset($data["ISBN:{$isbn}"])) {
        $book = $data["ISBN:{$isbn}"];
        $result['title']     = cleanText($book['title'] ?? '');
        $result['isbn']      = $isbn;
        $result['publisher'] = cleanText($book['publishers'][0]['name'] ?? '');
        $result['year']      = cleanText($book['publish_date'] ?? '');
        $result['cover_url'] = $book['cover']['large'] ?? $book['cover']['medium'] ?? '';

        if (!empty($book['authors'])) {
            $authors = array_map(fn($a) => $a['name'], $book['authors']);
            $result['author'] = implode(', ', $authors);

            // Fetch first author bio
            $authorKey = $book['authors'][0]['url'] ?? '';
            if ($authorKey) {
                $authorKey = preg_replace('#https?://openlibrary\.org#', '', $authorKey);
                $authorData = fetchUrl("https://openlibrary.org{$authorKey}.json");
                if ($authorData) {
                    $bio = $authorData['bio'] ?? '';
                    if (is_array($bio)) $bio = $bio['value'] ?? '';
                    $result['author_bio'] = cleanText($bio);
                }
            }
        }

        $desc = $book['excerpts'][0]['text'] ?? '';
        $result['description'] = cleanText($desc);
    }
} else {
    // Title search
    $encoded = urlencode($query);
    $olUrl = "https://openlibrary.org/search.json?title={$encoded}&limit=1&fields=key,title,author_name,isbn,publisher,first_publish_year,cover_i,first_sentence";
    $data  = fetchUrl($olUrl);

    if ($data && !empty($data['docs'])) {
        $doc = $data['docs'][0];
        $result['title']     = cleanText($doc['title'] ?? '');
        $result['isbn']      = !empty($doc['isbn']) ? $doc['isbn'][0] : '';
        $result['publisher'] = cleanText(!empty($doc['publisher']) ? $doc['publisher'][0] : '');
        $result['year']      = cleanText($doc['first_publish_year'] ?? '');
        $result['author']    = cleanText(!empty($doc['author_name']) ? implode(', ', $doc['author_name']) : '');

        if (!empty($doc['cover_i'])) {
            $result['cover_url'] = "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-L.jpg";
        }

        $sentence = $doc['first_sentence'] ?? '';
        if (is_array($sentence)) $sentence = $sentence['value'] ?? '';
        $result['description'] = cleanText($sentence);

        // Author page for bio
        if (!empty($doc['key'])) {
            $workData = fetchUrl("https://openlibrary.org{$doc['key']}.json");
            if ($workData) {
                $desc = $workData['description'] ?? '';
                if (is_array($desc)) $desc = $desc['value'] ?? '';
                if ($desc) $result['description'] = cleanText($desc);
            }
        }
    }
}

/* ── 2. Fallback: Google Books ────────────────────────── */
if (empty($result['title'])) {
    $encoded = urlencode($query);
    $field   = ($type === 'isbn') ? 'isbn:' : '';
    $gbUrl   = "https://www.googleapis.com/books/v1/volumes?q={$field}{$encoded}&maxResults=1";
    $gbData  = fetchUrl($gbUrl);

    if ($gbData && !empty($gbData['items'])) {
        $vol  = $gbData['items'][0]['volumeInfo'] ?? [];
        $result['title']       = cleanText($vol['title'] ?? '');
        $result['author']      = cleanText(!empty($vol['authors']) ? implode(', ', $vol['authors']) : '');
        $result['description'] = cleanText($vol['description'] ?? '');
        $result['publisher']   = cleanText($vol['publisher'] ?? '');
        $result['year']        = cleanText($vol['publishedDate'] ?? '');
        if (!empty($vol['industryIdentifiers'])) {
            foreach ($vol['industryIdentifiers'] as $id) {
                if ($id['type'] === 'ISBN_13' || $id['type'] === 'ISBN_10') {
                    $result['isbn'] = $id['identifier'];
                    break;
                }
            }
        }
        $imgLinks = $vol['imageLinks'] ?? [];
        $result['cover_url'] = $imgLinks['thumbnail'] ?? $imgLinks['smallThumbnail'] ?? '';
        // Upgrade Google Books image to larger size
        if ($result['cover_url']) {
            $result['cover_url'] = str_replace('zoom=1', 'zoom=3', $result['cover_url']);
            $result['cover_url'] = str_replace('http://', 'https://', $result['cover_url']);
        }
    }
}

if (empty($result['title'])) {
    echo json_encode(['error' => 'Book not found. Try a different title or ISBN.']);
} else {
    echo json_encode($result);
}
