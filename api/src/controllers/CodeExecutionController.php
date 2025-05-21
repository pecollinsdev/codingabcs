<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\CodeExecutionService;

/**
 * Controller for executing code submitted by users
 * 
 * Handles code execution requests and returns results
 */
class CodeExecutionController extends Controller {
    /**
     * @var CodeExecutionService Service to handle code execution
     */
    private CodeExecutionService $codeExecutionService;

    /**
     * Constructor for the CodeExecutionController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->codeExecutionService = new CodeExecutionService();
    }

    /**
     * Execute submitted code and return the result
     * 
     * Endpoint: POST /api/code/execute
     * Required fields:
     *   - language: Programming language to execute
     *   - code: The code to be executed
     * Optional fields:
     *   - stdin: Standard input to provide to the program
     *   - args: Command line arguments for the program
     *   - hidden_input: Hidden test input
     *   - expected_output: Expected output for verification
     *
     * @return void Sends JSON response with execution results
     */
    public function execute(): void {
        try {
            $data = $this->request->getData();

            // Validate required fields
            if (!isset($data['language']) || !isset($data['code'])) {
                $this->respondValidationError([
                    'language' => 'Language is required',
                    'code' => 'Code is required'
                ]);
                return;
            }

            // Execute the code
            $result = $this->codeExecutionService->executeCode(
                $data['language'],
                $data['code'],
                $data['stdin'] ?? '',
                $data['args'] ?? [],
                $data['hidden_input'] ?? null,
                $data['expected_output'] ?? null
            );

            // Check for error status first
            if ($result['status'] === 'error') {
                $this->respond([
                    'status' => 'error',
                    'message' => $result['message']
                ]);
                return;
            }

            // If no errors, return the successful result
            $this->respond([
                'status' => 'success',
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            $this->respondError('Code execution failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get list of supported programming languages
     * 
     * Endpoint: GET /api/code/languages
     * Returns a list of all programming languages that can be used for code execution
     *
     * @return void Sends JSON response with supported languages
     */
    public function getSupportedLanguages(): void {
        try {
            $languages = $this->codeExecutionService->getSupportedLanguages();
            
            $this->respond([
                'status' => 'success',
                'data' => $languages
            ]);
        } catch (\Exception $e) {
            $this->respondError('Failed to get supported languages', 500);
        }
    }
}