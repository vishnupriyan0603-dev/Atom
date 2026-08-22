<?php

namespace Atom\PersonalModel;

use Atom\Database\Connection;

class AtomPersonalModel
{
    private PersonalProfile $profile;
    private FeedbackManager $feedbackManager;
    private TrainingExampleRepository $trainingRepo;

    public function __construct(?Connection $connection, ?int $projectId, ?int $sessionId)
    {
        $this->profile          = new PersonalProfile($connection, $projectId);
        $this->feedbackManager  = new FeedbackManager($connection, $sessionId, $this->profile);
        $this->trainingRepo     = new TrainingExampleRepository($connection);
    }

    public function getProfile(): PersonalProfile
    {
        return $this->profile;
    }

    public function getFeedbackManager(): FeedbackManager
    {
        return $this->feedbackManager;
    }

    public function getTrainingRepo(): TrainingExampleRepository
    {
        return $this->trainingRepo;
    }

    /**
     * Add a training example with full optimization rules applied.
     *
     * @return array ['action' => 'inserted'|'merged'|'skipped'|'merged_response', 'id' => int|null, 'reason' => string]
     */
    public function addTrainingExample(
        string  $userInput,
        string  $preferredResponse,
        ?string $category       = null,
        ?string $contextSummary = null,
        string  $source         = 'user_approved',
        string  $quality        = 'GOOD'
    ): array {
        return $this->trainingRepo->add(
            $userInput,
            $preferredResponse,
            $category,
            $contextSummary,
            $source,
            $quality
        );
    }

    /**
     * Run a full optimization pass over all training data (rule 12).
     */
    public function optimizeTrainingData(): array
    {
        return $this->trainingRepo->optimize();
    }

    /**
     * Decorates the system message with current communication preferences.
     */
    public function getPersonalizedSystemPrompt(): string
    {
        $prefs = $this->profile->getPreferences();
        if (empty($prefs)) {
            return '';
        }

        $prompt = "\n--- ATOM PERSONAL COMMUNICATION PREFERENCES ---\n";
        $prompt .= "Follow these personal user preferences carefully:\n";
        foreach ($prefs as $pref) {
            $prompt .= "- " . str_replace('_', ' ', $pref['preference_key']) . ": " . $pref['preference_value'] . " (Source: " . $pref['source'] . ")\n";
        }
        $prompt .= "------------------------------------------------\n";
        return $prompt;
    }
}
