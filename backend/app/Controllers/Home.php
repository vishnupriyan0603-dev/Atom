<?php

namespace App\Controllers;

class Home extends BaseController
{
    private function getWebDir(): string
    {
        $path = realpath(FCPATH . '../../frontend/web');
        return $path ?: '';
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

        // Serve real static assets under /admin/ (e.g. /admin/js/shared.js) directly,
        // falling back to rendering a PHP page otherwise.
        $webDir = $this->getWebDir();
        $candidate = $webDir ? realpath($webDir . '/admin/' . $page) : false;
        if ($candidate && is_file($candidate) && strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) !== 'php') {
            $mimeTypes = [
                'css'  => 'text/css',
                'js'   => 'application/javascript',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'svg'  => 'image/svg+xml',
                'ico'  => 'image/x-icon',
                'woff' => 'font/woff',
                'woff2'=> 'font/woff2',
            ];
            $ext = pathinfo($candidate, PATHINFO_EXTENSION);
            return $this->response
                ->setContentType($mimeTypes[$ext] ?? 'application/octet-stream')
                ->setHeader('Cache-Control', 'max-age=3600, public')
                ->setBody(file_get_contents($candidate));
        }

        return $this->renderPhpFile('admin/' . $page . '.php');
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

        $filePath = realpath($webDir . '/' . $relativePath);
        if (!$filePath || !str_starts_with($filePath, $webDir) || !file_exists($filePath)) {
            return $this->response->setStatusCode(404)->setBody('Page not found');
        }

        // Execute PHP file using output buffering
        ob_start();
        try {
            include $filePath;
        } catch (\Throwable $e) {
            ob_end_clean();
            log_message('error', '[ATOM PAGE] Failed to render ' . $relativePath . ': ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Error rendering template');
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

        $filePath = realpath($webDir . '/' . $path);
        if (!$filePath || !str_starts_with($filePath, $webDir)) {
            return $this->response->setStatusCode(404);
        }

        if (!file_exists($filePath)) {
            return $this->response->setStatusCode(404);
        }

        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'html' => 'text/html',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
        ];

        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        // If it's a php file, render it rather than serving raw source code
        if ($ext === 'php') {
            return $this->renderPhpFile($path);
        }

        $content = file_get_contents($filePath);

        return $this->response
            ->setContentType($mime)
            ->setHeader('Cache-Control', 'max-age=3600, public')
            ->setBody($content);
    }
}
