<?php

namespace App\Controllers;

class Home extends BaseController
{
    private function getWebDir(): string
    {
        $candidates = [
            realpath(FCPATH . '../../frontend/web'),
            realpath(FCPATH . '../frontend/web'),
            realpath(ROOTPATH . '../frontend/web'),
            realpath(ROOTPATH . 'frontend/web'),
            realpath(dirname(FCPATH, 2) . '/frontend/web'),
            'E:/xampp/htdocs/my work/Atom/frontend/web',
        ];

        foreach ($candidates as $c) {
            if ($c && is_dir($c)) {
                return $c;
            }
        }
        return '';
    }

    public function index()
    {
        return $this->renderPhpFile('index.php');
    }

    public function chat()
    {
        return $this->renderPhpFile('chat.php');
    }

    public function admin(...$segments)
    {
        $page = implode('/', $segments);
        if (empty($page) || $page === '/') {
            $page = 'index';
        }

        // Strip trailing .php if user or redirect included it
        $cleanPage = preg_replace('/\.php$/i', '', $page);

        // Serve real static assets under /admin/ (e.g. /admin/js/shared.js, css, images) directly
        $webDir = $this->getWebDir();
        $candidate = $webDir ? realpath($webDir . '/admin/' . $page) : false;
        if ($candidate && is_file($candidate) && strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) !== 'php') {
            $mimeTypes = [
                'css'   => 'text/css',
                'js'    => 'application/javascript',
                'png'   => 'image/png',
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'gif'   => 'image/gif',
                'svg'   => 'image/svg+xml',
                'ico'   => 'image/x-icon',
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
            ];
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            return $this->response
                ->setContentType($mimeTypes[$ext] ?? 'application/octet-stream')
                ->setHeader('Cache-Control', 'max-age=3600, public')
                ->setBody(file_get_contents($candidate));
        }

        return $this->renderPhpFile('admin/' . $cleanPage . '.php');
    }

    /**
     * Executes a PHP page view inside the frontend/web directory and returns its content.
     */
    private function renderPhpFile(string $relativePath)
    {
        $webDir = $this->getWebDir();
        if (!$webDir) {
            return view('welcome_message');
        }

        $cleanRel = ltrim($relativePath, '/\\');
        $filePath = realpath($webDir . '/' . $cleanRel);

        // Direct path check if realpath returns false
        if (!$filePath || !file_exists($filePath)) {
            $directPath = $webDir . '/' . $cleanRel;
            if (file_exists($directPath)) {
                $filePath = $directPath;
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            return $this->response->setStatusCode(404)->setBody('Page not found: ' . esc($relativePath));
        }

        // Execute PHP file using output buffering
        ob_start();
        try {
            include $filePath;
        } catch (\Throwable $e) {
            ob_end_clean();
            log_message('error', '[ATOM PAGE] Failed to render ' . $relativePath . ': ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Error rendering template: ' . esc($e->getMessage()));
        }
        $output = ob_get_clean();

        return $this->response->setContentType('text/html')->setBody($output);
    }

    public function serve(...$segments)
    {
        $path = implode('/', $segments);
        $webDir = $this->getWebDir();
        if (!$webDir) {
            return $this->response->setStatusCode(404);
        }

        $cleanPath = ltrim($path, '/\\');
        $filePath = realpath($webDir . '/' . $cleanPath);
        if (!$filePath || !file_exists($filePath)) {
            $direct = $webDir . '/' . $cleanPath;
            if (file_exists($direct)) {
                $filePath = $direct;
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            return $this->response->setStatusCode(404);
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'html'  => 'text/html',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
        ];

        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        // If it's a php file, render it rather than serving raw source code
        if ($ext === 'php') {
            return $this->renderPhpFile($cleanPath);
        }

        $content = file_get_contents($filePath);

        return $this->response
            ->setContentType($mime)
            ->setHeader('Cache-Control', 'max-age=3600, public')
            ->setBody($content);
    }
}
