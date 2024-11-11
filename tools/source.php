<?php
function get_source($params) {
    if (isset($_GET['url']) && filter_var($_GET['url'], FILTER_VALIDATE_URL)) {
        $url = $_GET['url'];

        $htmlContent = @file_get_contents($url);

        if ($htmlContent === false) {
            return [
                "status" => "error",
                "message" => "No se pudo obtener el código fuente. Verifica la URL e inténtalo de nuevo."
            ];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($htmlContent);

        $files = [];

        $files[] = [
            'name' => 'index.html',
            'size' => strlen($htmlContent),
            'type' => 'html',
            'url' => $url
        ];

        foreach ($dom->getElementsByTagName('link') as $link) {
            if ($link->getAttribute('rel') === 'stylesheet') {
                $cssUrl = $link->getAttribute('href');
                $absoluteCssUrl = resolve_url($url, $cssUrl);
                
                $cssFilePath = download_temp_file($absoluteCssUrl);
                if ($cssFilePath) {
                    $files[] = [
                        'name' => basename($cssFilePath),
                        'size' => filesize($cssFilePath),
                        'type' => 'css',
                        'url' => $absoluteCssUrl
                    ];
                    unlink($cssFilePath);
                }
            }

            if ($link->getAttribute('rel') === 'icon' || $link->getAttribute('rel') === 'shortcut icon') {
                $faviconUrl = $link->getAttribute('href');
                $absoluteFaviconUrl = resolve_url($url, $faviconUrl);
                
                $faviconFilePath = download_temp_file($absoluteFaviconUrl);
                if ($faviconFilePath) {
                    $files[] = [
                        'name' => basename($faviconFilePath),
                        'size' => filesize($faviconFilePath),
                        'type' => 'favicon',
                        'url' => $absoluteFaviconUrl
                    ];
                    unlink($faviconFilePath);
                }
            }
        }

        foreach ($dom->getElementsByTagName('script') as $script) {
            $jsUrl = $script->getAttribute('src');
            if ($jsUrl) {
                $absoluteJsUrl = resolve_url($url, $jsUrl);

                $jsFilePath = download_temp_file($absoluteJsUrl);
                if ($jsFilePath) {
                    $files[] = [
                        'name' => basename($jsFilePath),
                        'size' => filesize($jsFilePath),
                        'type' => 'js',
                        'url' => $absoluteJsUrl
                    ];
                    unlink($jsFilePath);
                }
            }
        }

        foreach ($dom->getElementsByTagName('img') as $img) {
            $imgUrl = $img->getAttribute('src');
            if ($imgUrl) {
                $absoluteImgUrl = resolve_url($url, $imgUrl);

                $imgFilePath = download_temp_file($absoluteImgUrl);
                if ($imgFilePath) {
                    $files[] = [
                        'name' => basename($imgFilePath),
                        'size' => filesize($imgFilePath),
                        'type' => 'img',
                        'url' => $absoluteImgUrl
                    ];
                    unlink($imgFilePath);
                }
            }
        }

        foreach ($dom->getElementsByTagName('audio') as $audio) {
            $audioUrl = $audio->getAttribute('src');
            if ($audioUrl) {
                $absoluteAudioUrl = resolve_url($url, $audioUrl);

                $audioFilePath = download_temp_file($absoluteAudioUrl);
                if ($audioFilePath) {
                    $files[] = [
                        'name' => basename($audioFilePath),
                        'size' => filesize($audioFilePath),
                        'type' => 'audio',
                        'url' => $absoluteAudioUrl
                    ];
                    unlink($audioFilePath);
                }
            }
        }

        foreach ($dom->getElementsByTagName('video') as $video) {
            $videoUrl = $video->getAttribute('src');
            if ($videoUrl) {
                $absoluteVideoUrl = resolve_url($url, $videoUrl);

                $videoFilePath = download_temp_file($absoluteVideoUrl);
                if ($videoFilePath) {
                    $files[] = [
                        'name' => basename($videoFilePath),
                        'size' => filesize($videoFilePath),
                        'type' => 'video',
                        'url' => $absoluteVideoUrl // URL de origen
                    ];
                    unlink($videoFilePath);
                }
            }
        }

        foreach ($dom->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');
            if (preg_match('/\.(pdf|docx|xlsx|mp3|mp4)$/i', $href)) {
                $absoluteLinkUrl = resolve_url($url, $href);
                $files[] = [
                    'name' => basename($absoluteLinkUrl),
                    'size' => 0,
                    'type' => get_file_type_from_extension($absoluteLinkUrl),
                    'url' => $absoluteLinkUrl
                ];
            }
        }

        return [
            "status" => "success",
            "message" => "La extracción de recursos fue un éxito.",
            "files" => $files
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Formato de comando inválido. Debe incluir una URL válida."
        ];
    }
}

function resolve_url($base, $relative) {
    if (parse_url($relative, PHP_URL_SCHEME) != '') return $relative;
    if ($relative[0] == '/') return parse_url($base, PHP_URL_SCHEME) . '://' . parse_url($base, PHP_URL_HOST) . $relative;
    return rtrim($base, '/') . '/' . $relative;
}

function download_temp_file($url) {
    $tempDir = 'temp';
    if (!is_dir($tempDir)) mkdir($tempDir);

    $content = @file_get_contents($url);
    if ($content === false) return null;

    $filePath = $tempDir . '/' . basename($url);
    file_put_contents($filePath, $content);
    return $filePath;
}

function get_file_type_from_extension($fileUrl) {
    $extension = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'pdf':
            return 'pdf';
        case 'mp3':
            return 'audio';
        case 'mp4':
            return 'video';
        case 'docx':
            return 'docx';
        case 'xlsx':
            return 'xlsx';
        default:
            return 'unknown';
    }
}

$response = get_source($_GET);
echo json_encode($response);
?>
