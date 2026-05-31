<?php

namespace Database\Seeders;

use App\Data\Answers\BinaryAnswerData;
use App\Data\Answers\MultipleChoiceAnswerData;
use App\Data\Answers\NumberAnswerData;
use App\Data\Answers\SingleChoiceAnswerData;
use App\Data\Answers\TextAnswerData;
use App\Enums\QuestionStatusEnum;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuestionType;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('userType', fn ($q) => $q->where('user_type', 'admin'))->firstOrFail();

        $types = QuestionType::all()->keyBy('question_type');

        $this->seedBinaryQuestions($admin->id, $types['binary']->id);
        $this->seedSingleChoiceQuestions($admin->id, $types['single_choice']->id);
        $this->seedMultipleChoiceQuestions($admin->id, $types['multiple_choice']->id);
        $this->seedNumberInputQuestions($admin->id, $types['number_input']->id);
        $this->seedTextInputQuestions($admin->id, $types['text_input']->id);
    }

    private function createQuestion(string $text, ?string $hint, int $typeId, int $adminId): Question
    {
        return Question::create([
            'question_text' => $text,
            'question_hint' => $hint,
            'question_type_id' => $typeId,
            'question_status' => QuestionStatusEnum::Active,
            'created_by_id' => $adminId,
        ]);
    }

    private function seedBinaryQuestions(int $adminId, int $typeId): void
    {
        $questions = [
            ['text' => 'A linked list is a linear data structure.', 'hint' => 'Think about how elements are connected.', 'answer' => true],
            ['text' => 'TCP guarantees packet delivery and ordering.', 'hint' => 'Compare TCP vs UDP.', 'answer' => true],
            ['text' => 'A Binary Search Tree is always balanced.', 'hint' => 'Think about insertion order.', 'answer' => false],
            ['text' => 'IPv6 addresses are 128 bits long.', 'hint' => 'Compare with IPv4 which is 32 bits.', 'answer' => true],
            ['text' => 'HTTP uses UDP as its transport protocol.', 'hint' => 'HTTP is a reliable protocol.', 'answer' => false],
            ['text' => 'Quicksort is a stable sorting algorithm.', 'hint' => 'Stability means equal elements maintain original order.', 'answer' => false],
            ['text' => 'A stack follows the LIFO (Last In First Out) principle.', 'hint' => 'Think of a stack of plates.', 'answer' => true],
            ['text' => 'In a min-heap, the root node contains the smallest element.', 'hint' => 'Think about heap property.', 'answer' => true],
            ['text' => 'SQL JOIN and SQL UNION perform the same operation.', 'hint' => 'JOIN combines columns, UNION combines rows.', 'answer' => false],
            ['text' => 'An array can store elements of different data types in Java.', 'hint' => 'Think about strongly-typed languages.', 'answer' => false],
        ];

        foreach ($questions as $q) {
            $question = $this->createQuestion($q['text'], $q['hint'], $typeId, $adminId);
            QuestionAnswer::create([
                'question_id' => $question->id,
                'answer_data' => (new BinaryAnswerData(value: $q['answer']))->toArray(),
            ]);
        }
    }

    private function seedSingleChoiceQuestions(int $adminId, int $typeId): void
    {
        $questions = [
            [
                'text' => 'What is the time complexity of binary search?',
                'hint' => 'The search space is halved on each step.',
                'options' => ['O(n)', 'O(log n)', 'O(n²)', 'O(1)'],
                'correct' => 1,
            ],
            [
                'text' => 'Which data structure follows the FIFO principle?',
                'hint' => 'Think of a line at a ticket counter.',
                'options' => ['Stack', 'Tree', 'Queue', 'Graph'],
                'correct' => 2,
            ],
            [
                'text' => 'What is the default port number for HTTP?',
                'hint' => 'HTTPS uses 443.',
                'options' => ['21', '22', '443', '80'],
                'correct' => 3,
            ],
            [
                'text' => 'Which layer of the OSI model does TCP operate at?',
                'hint' => 'It handles end-to-end communication.',
                'options' => ['Network', 'Data Link', 'Transport', 'Application'],
                'correct' => 2,
            ],
            [
                'text' => 'What does DNS stand for?',
                'hint' => 'It translates domain names to IP addresses.',
                'options' => ['Data Network Service', 'Domain Name System', 'Dynamic Node Service', 'Distributed Name Server'],
                'correct' => 1,
            ],
            [
                'text' => 'Which sorting algorithm has the best average-case time complexity?',
                'hint' => 'It uses divide and conquer.',
                'options' => ['Bubble Sort', 'Insertion Sort', 'Merge Sort', 'Selection Sort'],
                'correct' => 2,
            ],
            [
                'text' => 'What data structure is used internally for function call management?',
                'hint' => 'Recursion depends on this.',
                'options' => ['Queue', 'Stack', 'Heap', 'Graph'],
                'correct' => 1,
            ],
            [
                'text' => 'Which protocol is used to send emails?',
                'hint' => 'It stands for Simple Mail Transfer Protocol.',
                'options' => ['FTP', 'IMAP', 'SMTP', 'POP3'],
                'correct' => 2,
            ],
            [
                'text' => 'What is the worst-case time complexity of quicksort?',
                'hint' => 'This happens when the pivot is always the smallest or largest element.',
                'options' => ['O(n log n)', 'O(n)', 'O(n²)', 'O(log n)'],
                'correct' => 2,
            ],
            [
                'text' => 'Which data structure is best suited for implementing a priority queue?',
                'hint' => 'It keeps the highest or lowest priority element at the top.',
                'options' => ['Array', 'Linked List', 'Heap', 'Stack'],
                'correct' => 2,
            ],
            [
                'text' => 'What is the average-case time complexity for searching in a hash table?',
                'hint' => 'No traversal needed if the hash function is good.',
                'options' => ['O(n)', 'O(log n)', 'O(1)', 'O(n²)'],
                'correct' => 2,
            ],
            [
                'text' => 'In an OSI model, which layer handles encryption and decryption?',
                'hint' => 'It prepares data for the application layer.',
                'options' => ['Session', 'Transport', 'Presentation', 'Application'],
                'correct' => 2,
            ],
        ];

        foreach ($questions as $q) {
            $question = $this->createQuestion($q['text'], $q['hint'], $typeId, $adminId);

            $createdOptions = [];
            foreach ($q['options'] as $i => $optionText) {
                $createdOptions[] = Option::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'display_order' => $i + 1,
                ]);
            }

            QuestionAnswer::create([
                'question_id' => $question->id,
                'answer_data' => (new SingleChoiceAnswerData(option_id: $createdOptions[$q['correct']]->id))->toArray(),
            ]);
        }
    }

    private function seedMultipleChoiceQuestions(int $adminId, int $typeId): void
    {
        $questions = [
            [
                'text' => 'Which of the following are linear data structures?',
                'hint' => 'Linear means elements are arranged sequentially.',
                'options' => ['Array', 'Stack', 'Binary Tree', 'Queue', 'Graph', 'Linked List'],
                'correct' => [0, 1, 3, 5],
            ],
            [
                'text' => 'Which of the following are valid HTTP request methods?',
                'hint' => 'RESTful APIs commonly use these.',
                'options' => ['GET', 'SEND', 'POST', 'DELETE', 'FETCH', 'PUT'],
                'correct' => [0, 2, 3, 5],
            ],
            [
                'text' => 'Which sorting algorithms have O(n log n) average time complexity?',
                'hint' => 'These are considered efficient sorting algorithms.',
                'options' => ['Bubble Sort', 'Merge Sort', 'Quick Sort', 'Heap Sort', 'Insertion Sort'],
                'correct' => [1, 2, 3],
            ],
            [
                'text' => 'Which of the following are ACID properties of a database?',
                'hint' => 'These ensure reliable database transactions.',
                'options' => ['Atomicity', 'Consistency', 'Concurrency', 'Isolation', 'Durability'],
                'correct' => [0, 1, 3, 4],
            ],
            [
                'text' => 'Which of the following are features of Object-Oriented Programming?',
                'hint' => 'Core pillars of OOP.',
                'options' => ['Encapsulation', 'Compilation', 'Inheritance', 'Polymorphism', 'Abstraction'],
                'correct' => [0, 2, 3, 4],
            ],
            [
                'text' => 'Which of the following are types of SQL JOINs?',
                'hint' => 'These combine rows from two or more tables.',
                'options' => ['INNER JOIN', 'OUTER JOIN', 'LEFT JOIN', 'CROSS JOIN', 'DIAGONAL JOIN'],
                'correct' => [0, 1, 2, 3],
            ],
            [
                'text' => 'Which of the following are binary tree traversal methods?',
                'hint' => 'Think about the order of visiting root, left, and right nodes.',
                'options' => ['Inorder', 'Preorder', 'Sideways', 'Postorder', 'Level Order'],
                'correct' => [0, 1, 3, 4],
            ],
            [
                'text' => 'Which protocols operate at the Application layer of the OSI model?',
                'hint' => 'These are protocols users interact with directly.',
                'options' => ['HTTP', 'TCP', 'FTP', 'IP', 'DNS', 'SMTP'],
                'correct' => [0, 2, 4, 5],
            ],
        ];

        foreach ($questions as $q) {
            $question = $this->createQuestion($q['text'], $q['hint'], $typeId, $adminId);

            $createdOptions = [];
            foreach ($q['options'] as $i => $optionText) {
                $createdOptions[] = Option::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'display_order' => $i + 1,
                ]);
            }

            $correctIds = array_map(fn ($i) => $createdOptions[$i]->id, $q['correct']);

            QuestionAnswer::create([
                'question_id' => $question->id,
                'answer_data' => (new MultipleChoiceAnswerData(option_ids: $correctIds))->toArray(),
            ]);
        }
    }

    private function seedNumberInputQuestions(int $adminId, int $typeId): void
    {
        $questions = [
            ['text' => 'How many bits are in one byte?', 'hint' => null, 'answer' => 8],
            ['text' => 'What is the value of 2 to the power of 10?', 'hint' => 'Commonly used in computing (1 KB).', 'answer' => 1024],
            ['text' => 'What is the maximum value of an unsigned 8-bit integer?', 'hint' => '2^8 - 1', 'answer' => 255],
            ['text' => 'How many layers are in the OSI model?', 'hint' => 'From Physical to Application.', 'answer' => 7],
            ['text' => 'What is the minimum number of edges in a connected graph with 10 vertices?', 'hint' => 'Think of a tree structure.', 'answer' => 9],
            ['text' => 'How many bits does an IPv4 address have?', 'hint' => 'It is divided into 4 octets.', 'answer' => 32],
        ];

        foreach ($questions as $q) {
            $question = $this->createQuestion($q['text'], $q['hint'], $typeId, $adminId);
            QuestionAnswer::create([
                'question_id' => $question->id,
                'answer_data' => (new NumberAnswerData(value: $q['answer']))->toArray(),
            ]);
        }
    }

    private function seedTextInputQuestions(int $adminId, int $typeId): void
    {
        $questions = [
            [
                'text' => 'Explain what Big O notation is and why it is used.',
                'hint' => 'Focus on time and space complexity.',
                'model_answer' => 'Big O notation describes the upper bound of an algorithm\'s time or space complexity as the input size grows. It helps compare algorithm efficiency independent of hardware.',
            ],
            [
                'text' => 'What is the difference between TCP and UDP? When would you use each?',
                'hint' => 'Think about reliability vs speed.',
                'model_answer' => 'TCP is connection-oriented, reliable, and guarantees delivery and ordering. UDP is connectionless, faster, but does not guarantee delivery. Use TCP for web, email; UDP for video streaming, gaming.',
            ],
            [
                'text' => 'What is a hash collision and how can it be resolved?',
                'hint' => 'Two keys mapping to the same index.',
                'model_answer' => 'A hash collision occurs when two different keys produce the same hash value. It can be resolved using chaining (linked list at each bucket) or open addressing (probing for the next empty slot).',
            ],
            [
                'text' => 'Explain the difference between a process and a thread.',
                'hint' => 'Think about memory and resource sharing.',
                'model_answer' => 'A process is an independent program in execution with its own memory space. A thread is a unit of execution within a process that shares memory with other threads of the same process. Threads are lighter and faster to create.',
            ],
            [
                'text' => 'What is a deadlock in operating systems? State the four conditions required for deadlock.',
                'hint' => 'Coffman conditions.',
                'model_answer' => 'Deadlock is a state where processes wait forever for resources held by each other. The four conditions are: Mutual Exclusion, Hold and Wait, No Preemption, and Circular Wait.',
            ],
        ];

        foreach ($questions as $q) {
            $question = $this->createQuestion($q['text'], $q['hint'], $typeId, $adminId);
            QuestionAnswer::create([
                'question_id' => $question->id,
                'answer_data' => (new TextAnswerData(value: '', model_answer: $q['model_answer']))->toArray(),
            ]);
        }
    }
}
