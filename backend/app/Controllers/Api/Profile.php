<?php

namespace App\Controllers\Api;

use Atom\PersonalModel\OwnerProfileManager;

class Profile extends BaseApiController
{
    private OwnerProfileManager $profileManager;

    public function __construct()
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));

        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);

        $dbHost = \Atom\Config\Config::get('DB_HOST', 'localhost');
        $dbName = \Atom\Config\Config::get('DB_NAME', 'atom_assistant');
        $dbUser = \Atom\Config\Config::get('DB_USER', 'root');
        $dbPass = \Atom\Config\Config::get('DB_PASSWORD', '');
        $dbPort = \Atom\Config\Config::get('DB_PORT', '3306');

        $dbConnection = new \Atom\Database\Connection(
            $dbHost ?: 'localhost',
            $dbName ?: 'atom_assistant',
            $dbUser ?: 'root',
            $dbPass ?: '',
            $dbPort ?: '3306'
        );

        $this->profileManager = new OwnerProfileManager($dbConnection);
    }

    public function index()
    {
        $profile = $this->profileManager->getProfile();
        $biometrics = $this->profileManager->getBiometricSettings();
        
        return $this->respondSuccess([
            'profile' => $profile,
            'biometrics' => $biometrics
        ]);
    }

    public function updateProfile()
    {
        $data = $this->request->getJSON(true);
        $success = $this->profileManager->updateProfile($data);
        if ($success) {
            return $this->respondSuccess($this->profileManager->getProfile(), 'Profile updated');
        }
        return $this->respondError('Failed to update profile');
    }

    public function updateBiometrics()
    {
        $data = $this->request->getJSON(true);
        $enabled = (int)($data['face_data_enabled'] ?? 0);
        $path = $data['face_photo_path'] ?? '';
        
        $success = $this->profileManager->updateBiometricSettings($enabled, $path);
        if ($success) {
            return $this->respondSuccess($this->profileManager->getBiometricSettings(), 'Biometrics updated');
        }
        return $this->respondError('Failed to update biometrics');
    }

    public function uploadImage()
    {
        $file = $this->request->getFile('image');
        if (!$file || !$file->isValid()) {
            return $this->respondError('Invalid image file');
        }

        $workspaceRoot = dirname(ROOTPATH);
        $uploadDir = $workspaceRoot . '/storage/profile/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newName = $file->getRandomName();
        if ($file->move($uploadDir, $newName)) {
            $relativePath = 'storage/profile/' . $newName;
            return $this->respondSuccess(['path' => $relativePath], 'Image uploaded successfully');
        }

        return $this->respondError('Failed to save uploaded file');
    }

    public function uploadBiometricPhoto()
    {
        $file = $this->request->getFile('image');
        if (!$file || !$file->isValid()) {
            return $this->respondError('Invalid image file');
        }

        $workspaceRoot = dirname(ROOTPATH);
        $uploadDir = $workspaceRoot . '/storage/biometrics/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newName = $file->getRandomName();
        if ($file->move($uploadDir, $newName)) {
            $relativePath = 'storage/biometrics/' . $newName;
            return $this->respondSuccess(['path' => $relativePath], 'Biometric photo uploaded successfully');
        }

        return $this->respondError('Failed to save biometric photo');
    }

    public function exportData()
    {
        $data = $this->profileManager->exportUserData();
        return $this->respondSuccess($data, 'Data exported');
    }

    public function wipeConversations()
    {
        if ($this->profileManager->deleteConversations()) {
            return $this->respondSuccess(null, 'Conversations wiped successfully');
        }
        return $this->respondError('Failed to wipe conversations');
    }

    public function wipeMemories()
    {
        if ($this->profileManager->deleteMemories()) {
            return $this->respondSuccess(null, 'Memories and preferences wiped successfully');
        }
        return $this->respondError('Failed to wipe memories');
    }

    public function wipeTraining()
    {
        if ($this->profileManager->deleteTrainingData()) {
            return $this->respondSuccess(null, 'Training data wiped successfully');
        }
        return $this->respondError('Failed to wipe training data');
    }

    public function wipeKnowledge()
    {
        if ($this->profileManager->deleteKnowledgeData()) {
            return $this->respondSuccess(null, 'Knowledge data wiped successfully');
        }
        return $this->respondError('Failed to wipe knowledge data');
    }

    public function wipeFace()
    {
        if ($this->profileManager->deleteFaceData()) {
            return $this->respondSuccess(null, 'Face data wiped successfully');
        }
        return $this->respondError('Failed to wipe face data');
    }

    public function resetPersonalization()
    {
        if ($this->profileManager->resetPersonalization()) {
            return $this->respondSuccess(null, 'ATOM Personalization successfully reset');
        }
        return $this->respondError('Failed to reset ATOM personalization');
    }
}
