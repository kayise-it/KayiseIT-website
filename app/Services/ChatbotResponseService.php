<?php

namespace App\Services;

use App\Models\InternshipProgram;

class ChatbotResponseService
{
    protected $stopWords = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'can', 'do', 'for', 'from',
        'how', 'i', 'in', 'is', 'it', 'my', 'of', 'on', 'or', 'our', 'please', 'the',
        'to', 'we', 'what', 'when', 'where', 'with', 'you', 'your'
    ];

    /**
     * Kayise IT Chatbot Knowledge Base
     */
    protected $responses = [
        [
            'intent' => 'greeting',
            'questions' => ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings'],
            'response' => 'Hello. I am the KAYISE IT assistant. I can help with our services, training programs, opportunities, certifications, and partnerships.'
        ],
        [
            'intent' => 'services',
            'questions' => [
                'what services does kayise it offer',
                'what services do you offer',
                'services',
                'what can kayise it do',
                'website development',
                'it consulting',
                'cyber security training',
                'microsoft office training',
                'computer productivity'
            ],
            'response' => 'KAYISE IT offers drone building training, ICT skills training, 4IR technology training, cyber security training, Microsoft Office productivity training, website development, and IT consulting.'
        ],
        [
            'intent' => 'drone_training',
            'questions' => [
                'do you offer drone training',
                'drone training',
                'drone workshop',
                'can i learn drones',
                'drone building course',
                'build a drone'
            ],
            'response' => 'Yes. KAYISE IT offers practical drone building training. The course includes hands-on assembly, testing, and STEM-based learning for schools and institutions.'
        ],
        [
            'intent' => 'opportunities',
            'questions' => [
                'what opportunities are available',
                'internship',
                'internships',
                'job opportunities',
                'career opportunities',
                'hiring',
                'recruitment',
                'apply',
                'work with you'
            ],
            'response' => 'We have exciting internship and career opportunities available. You can view all open positions on our website under the Opportunities section. Visit our website or press 2 to hear more details.'
        ],
        [
            'intent' => 'training_programs',
            'questions' => [
                'training programs',
                'training and skills',
                'training skills',
                'skills training',
                'training',
                'courses available',
                'what courses',
                'certification courses',
                'ict training',
                'school training',
                'tvet training',
                'microsoft office training',
                'computer productivity',
                'entrepreneurship training',
                'build a drone'
            ],
            'response' => 'KAYISE IT offers Training & Skills programs for schools, TVET colleges, and institutions across South Africa.'
        ],
        [
            'intent' => 'certification',
            'questions' => [
                'certification',
                'certifications',
                'do you offer certificates',
                'certificate programs',
                'certified training'
            ],
            'response' => 'Yes. We offer recognized certifications upon completion of our training programs. All programs include industry-relevant certifications to enhance your professional credentials.'
        ],
        [
            'intent' => 'partnership',
            'questions' => [
                'partnership',
                'partnerships',
                'collaborate',
                'working together',
                'partnership opportunities',
                'partner with'
            ],
            'response' => 'We welcome partnerships with schools, organizations, and businesses. Our partnership programs are flexible and tailored to your needs. Contact us to discuss how we can work together.'
        ],
        [
            'intent' => 'contact',
            'questions' => [
                'contact',
                'contact information',
                'how do i contact',
                'phone number',
                'email',
                'get in touch'
            ],
            'response' => 'You can reach us at +27 87 702 2625 for voice calls, or on WhatsApp. Our office address is Suite 2, 2nd Floor, Nelbro Building, 39B Brown Street, Mbombela. You can also open the Contact page and use the map for directions.'
        ],
        [
            'intent' => 'location',
            'questions' => [
                'location',
                'located',
                'locate',
                'where are you located',
                'where is the company located',
                'company location',
                'address',
                'company address',
                'office location',
                'office address',
                'physical address',
                'directions',
                'direction',
                'map',
                'gps'
            ],
            'response' => 'KAYISE IT is located at Suite 2, 2nd Floor, Nelbro Building, 39B Brown Street, Mbombela. As a backup, you can also open the Contact page and click the map to get directions.'
        ],
        [
            'intent' => 'cost',
            'questions' => [
                'how much',
                'cost',
                'price',
                'pricing',
                'fees',
                'how much do you charge'
            ],
            'response' => 'Pricing varies depending on the program and your specific needs. Please visit our website for detailed pricing information or contact us for a custom quote.'
        ],
        [
            'intent' => 'transfer',
            'questions' => [
                'agent',
                'speak to someone',
                'talk to a person',
                'speak to a human',
                'representative',
                'customer service'
            ],
            'response' => 'I can transfer you to one of our team members. Please hold while I connect you.'
        ],
    ];

    /**
     * Get response for user input
     */
    public function getResponse(string $userInput, array $conversationHistory = []): string
    {
        $input = $this->normalizeText($userInput);
        $inputKeywords = $this->extractKeywords($input);
        $historyText = $this->normalizeText(implode(' ', array_slice($conversationHistory, -6)));
        $historyKeywords = $this->extractKeywords($historyText);
        
        if (empty($input)) {
            return 'I did not understand that. Could you please repeat your inquiry?';
        }

        // Strong keyword override for Training & Skills queries.
        if (in_array('training', $inputKeywords, true) && (in_array('skills', $inputKeywords, true) || in_array('skill', $inputKeywords, true))) {
            return $this->resolveIntentResponse('training_programs', '');
        }

        // Exact phrase matching first.
        foreach ($this->responses as $item) {
            foreach ($item['questions'] as $question) {
                if ($this->matchesQuestion($input, $inputKeywords, $question)) {
                    return $this->resolveIntentResponse($item['intent'], $item['response']);
                }
            }
        }

        // Keyword scoring fallback for natural/conversational messages.
        $bestScore = 0;
        $bestResponse = null;
        $bestIntent = null;

        foreach ($this->responses as $item) {
            $score = 0;

            foreach ($item['questions'] as $question) {
                $score += $this->scoreQuestionMatch($inputKeywords, $historyKeywords, $question);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestResponse = $item['response'];
                $bestIntent = $item['intent'];
            }
        }

        if ($bestResponse !== null && $bestScore >= 2) {
            return $this->resolveIntentResponse((string) $bestIntent, $bestResponse);
        }

        // Default response if no match
        return 'I\'m not sure about that. You can press 1 for services, 2 for opportunities, 3 for training programs, 4 for certification, 5 for contact, or 0 to speak to an agent.';
    }

    protected function normalizeText(string $text): string
    {
        $normalized = strtolower(trim($text));
        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized);
        $normalized = preg_replace('/\bopportuniess\b|\bopportunites\b|\boppertunities\b|\boppotunities\b/', 'opportunities', $normalized);
        $normalized = preg_replace('/\bopportunitys\b/', 'opportunities', $normalized);

        return preg_replace('/\s+/', ' ', $normalized ?? '');
    }

    protected function scoreQuestionMatch(array $inputKeywords, array $historyKeywords, string $question): int
    {
        $normalizedQuestion = $this->normalizeText($question);
        $keywords = $this->extractKeywords($normalizedQuestion);
        $score = 0;

        foreach ($keywords as $keyword) {
            if (in_array($keyword, $inputKeywords, true)) {
                $score += 2;
                continue;
            }

            if (in_array($keyword, $historyKeywords, true)) {
                $score += 1;
            }
        }

        return $score;
    }

    protected function matchesQuestion(string $input, array $inputKeywords, string $question): bool
    {
        $normalizedQuestion = $this->normalizeText($question);

        if ($normalizedQuestion === '') {
            return false;
        }

        if (!str_contains($normalizedQuestion, ' ')) {
            return in_array($normalizedQuestion, $inputKeywords, true);
        }

        $paddedInput = ' ' . $input . ' ';
        $paddedQuestion = ' ' . $normalizedQuestion . ' ';

        return str_contains($paddedInput, $paddedQuestion);
    }

    protected function extractKeywords(string $text): array
    {
        $parts = explode(' ', $text);
        $keywords = [];

        foreach ($parts as $part) {
            if (strlen($part) < 3) {
                continue;
            }

            if (in_array($part, $this->stopWords, true)) {
                continue;
            }

            $keywords[] = $part;
        }

        return array_values(array_unique($keywords));
    }

    /**
     * Get response for IVR menu option
     */
    public function getResponseByIntent(string $intent): string
    {
        foreach ($this->responses as $item) {
            if ($item['intent'] === $intent) {
                return $this->resolveIntentResponse($intent, $item['response']);
            }
        }

        return 'Thank you for your interest. How else can we help you?';
    }

    /**
     * Get all intents
     */
    public function getIntents(): array
    {
        return array_map(fn($item) => $item['intent'], $this->responses);
    }

    /**
     * Add custom response
     */
    public function addResponse(string $intent, array $questions, string $response): void
    {
        $this->responses[] = [
            'intent' => $intent,
            'questions' => $questions,
            'response' => $response,
        ];
    }

    protected function resolveIntentResponse(string $intent, string $fallbackResponse): string
    {
        if ($intent === 'opportunities') {
            return $this->getLiveOpportunitiesResponse();
        }

        if ($intent === 'training_programs') {
            return $this->getTrainingSkillsResponse();
        }

        if ($intent === 'contact') {
            return $this->contactResponse();
        }

        return $fallbackResponse;
    }

    protected function contactResponse(): string
    {
        $display = \App\Models\SiteSetting::displayedWhatsappNumber();
        $whatsapp = $display ? 'WhatsApp '.$display : 'WhatsApp on our Contact page';

        return 'You can reach us at +27 87 702 2625 for voice calls, or '.$whatsapp.'. Our office address is Suite 2, 2nd Floor, Nelbro Building, 39B Brown Street, Mbombela. You can also open the Contact page and use the map for directions.';
    }

    protected function getTrainingSkillsResponse(): string
    {
        return "Our Training & Skills programs include:\n"
            . "1. ICT Skills Training: computer literacy, internet skills, and digital communication.\n"
            . "2. Build a Drone Course: hands-on drone assembly, testing, and STEM learning.\n"
            . "3. Microsoft Office Training: Word, Excel, PowerPoint, and Outlook.\n"
            . "4. Computer Productivity: file management, collaboration tools, and digital workflow habits.\n"
            . "5. Cyber Security Training: online safety, password protection, and data security.\n"
            . "6. Entrepreneurship Training: business thinking, opportunity identification, and startup skills.\n"
            . "These programs are designed for schools, TVET colleges, and institutions. You can view full details on the Training & Skills page.";
    }

    protected function getLiveOpportunitiesResponse(): string
    {
        $today = now()->toDateString();

        $programs = InternshipProgram::query()
            ->active()
            ->whereDate('recruitment_end_date', '>=', $today)
            ->orderBy('recruitment_end_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('name')
            ->filter()
            ->values();

        if ($programs->isEmpty()) {
            return 'There are currently no open opportunities right now. Please check our Opportunities page again soon for new openings.';
        }

        $numberedNames = $programs
            ->values()
            ->map(fn($name, $index) => ($index + 1) . '. ' . $name)
            ->implode("\n");

        return "Yes, we currently have open opportunities:\n" . $numberedNames . "\nVisit the Opportunities page to view details and apply.";
    }
}
