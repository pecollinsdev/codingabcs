<?php

namespace App\Services;

class CodeExecutionService {
    private $apiUrl = 'https://emkc.org/api/v2/piston';

    /**
     * Execute code using Piston API
     * 
     * @param string $language Programming language to use
     * @param string $code Code to execute
     * @param string $stdin Standard input (optional)
     * @param array $args Command line arguments (optional)
     * @param string|null $hiddenInput Hidden input for test cases (optional)
     * @param string|null $expectedOutput Expected output for test cases (optional)
     * @return array Consistent JSON-like response: status, data/error
     */
    public function executeCode($language, $code, $stdin = '', $args = [], $hiddenInput = null, $expectedOutput = null) {
        try {
            $languageMap = [
                'python' => 'python3',
                'javascript' => 'javascript',
                'php' => 'php',
                'java' => 'java'
            ];
            $pistonLanguage = $languageMap[$language] ?? $language;
            
            // Set specific versions for languages
            $versionMap = [
                'javascript' => '18.15.0',
                'python3' => '3.10.0',
                'php' => '*',
                'java' => '15.0.2'
            ];
            $version = $versionMap[$pistonLanguage] ?? '*';

            // Log the language mapping
            error_log("Original language: " . $language);
            error_log("Mapped language: " . $pistonLanguage);

            // If hidden input is provided, we need to modify the code to test against it
            if ($hiddenInput !== null) {
                $testCases = json_decode($hiddenInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->errorResponse("Invalid hidden input format");
                }

                // Generate test code based on language
                if ($language === 'python') {
                    $testCode = $this->generatePythonTestCode($code, $testCases);
                    if (is_array($testCode) && $testCode['status'] === 'error') {
                        return $testCode;
                    }
                    $code = $testCode;
                } elseif ($language === 'javascript') {
                    $testCode = $this->generateJavaScriptTestCode($code, $testCases);
                    if (is_array($testCode) && $testCode['status'] === 'error') {
                        return $testCode;
                    }
                    $code = $testCode;
                } elseif ($language === 'php') {
                    $testCode = $this->generatePhpTestCode($code, $testCases);
                    if (is_array($testCode) && $testCode['status'] === 'error') {
                        return $testCode;
                    }
                    $code = $testCode;
                } elseif ($language === 'java') {
                    $testCode = $this->generateJavaTestCode($code, $testCases);
                    if (is_array($testCode) && $testCode['status'] === 'error') {
                        return $testCode;
                    }
                    $code = $testCode;
                } else {
                    return $this->errorResponse("Unsupported language for test cases");
                }
            }

            $payload = [
                'language' => $pistonLanguage,
                'version' => $version,
                'files' => [[
                    'name' => 'main.' . $this->getFileExtension($language),
                    'content' => $code
                ]],
                'stdin' => $stdin,
                'args' => $args,
                'compile_timeout' => 10000,
                'run_timeout' => 3000,
                'compile_memory_limit' => -1,
                'run_memory_limit' => -1
            ];

            // Log the payload for debugging
            error_log("Piston API payload: " . json_encode($payload));

            $ch = curl_init($this->apiUrl . '/execute');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log the response for debugging
            error_log("Response from Piston API: " . $response);

            if ($curlError) {
                return $this->errorResponse("CURL error: $curlError");
            }

            if ($httpCode !== 200) {
                return $this->errorResponse("Unexpected HTTP status code: $httpCode", $httpCode);
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->errorResponse("Invalid JSON response from Piston API");
            }

            // Check if the response has the expected structure
            if (!isset($decoded['run'])) {
                return $this->errorResponse("Invalid response structure from Piston API");
            }

            // Get the actual output and ensure it's properly formatted
            $actualOutput = trim($decoded['run']['stdout'] ?? '');
            $error = $decoded['run']['stderr'] ?? '';
            $compileError = $decoded['compile']['stderr'] ?? null;

            // Log the actual output for debugging
            error_log("Actual output: " . $actualOutput);
            error_log("Error: " . $error);
            error_log("Compile error: " . $compileError);

            // Format the response with more detailed output
            $formatted = [
                'output' => $actualOutput,
                'error' => $error,
                'exit_code' => $decoded['run']['code'] ?? null,
                'compile_error' => $compileError,
                'raw_response' => $decoded
            ];

            // If there's a compile error or runtime error, return it
            if ($compileError) {
                return [
                    'status' => 'error',
                    'message' => "Compilation Error:\n" . $compileError
                ];
            }
            if ($error) {
                return [
                    'status' => 'error',
                    'message' => "Runtime Error:\n" . $error
                ];
            }

            // If expected output is provided, compare and add result
            if ($expectedOutput !== null) {
                $actualOutput = $this->parseOutput($actualOutput);
                $expectedOutput = $this->parseOutput($expectedOutput);

                // Compare outputs
                $isCorrect = $this->compareOutputs($actualOutput, $expectedOutput);

                $formatted['is_correct'] = $isCorrect;
                $formatted['expected_output'] = $expectedOutput;
            }

            // Always return a success response with the formatted data
            return [
                'status' => 'success',
                'data' => $formatted
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Generate Python test code that runs the function against test cases
     */
    private function generatePythonTestCode($code, $testCases) {
        // Extract function name from the code
        preg_match('/def\s+(\w+)\s*\(/', $code, $matches);
        $functionName = $matches[1] ?? null;
        
        if (!$functionName) {
            return $this->errorResponse("Could not find function definition in code");
        }

        $testCode = $code . "\n\n";
        $testCode .= "def bool_to_str(val):\n";
        $testCode .= "    if isinstance(val, bool):\n";
        $testCode .= "        return 'True' if val else 'False'\n";
        $testCode .= "    return str(val)\n\n";
        $testCode .= "results = []\n";
        
        foreach ($testCases as $testCase) {
            if (is_array($testCase)) {
                // Handle multiple arguments
                $args = array_map(function($arg) {
                    if (is_string($arg)) {
                        return "'" . str_replace("'", "\\'", $arg) . "'";
                    } elseif (is_array($arg)) {
                        return json_encode($arg);
                    } elseif (is_bool($arg)) {
                        return $arg ? 'True' : 'False';
                    }
                    return $arg;
                }, $testCase);
                $testCode .= "results.append($functionName(" . implode(", ", $args) . "))\n";
            } else {
                // Handle single argument
                if (is_bool($testCase)) {
                    $arg = $testCase ? 'True' : 'False';
                } else {
                    $arg = is_string($testCase) ? "'" . str_replace("'", "\\'", $testCase) . "'" : $testCase;
                }
                $testCode .= "results.append($functionName($arg))\n";
            }
        }
        
        // Convert the results to a string representation that matches Python's True/False
        $testCode .= "print('[' + ', '.join(bool_to_str(x) for x in results) + ']')\n";
        
        return $testCode;
    }

    /**
     * Generate JavaScript test code that runs the function against test cases
     */
    private function generateJavaScriptTestCode($code, $testCases) {
        // Extract function name from the code
        preg_match('/function\s+(\w+)\s*\(/', $code, $matches);
        $functionName = $matches[1] ?? null;
        
        if (!$functionName) {
            return $this->errorResponse("Could not find function definition in code");
        }

        $testCode = $code . "\n\n";
        $testCode .= "const results = [];\n";
        
        foreach ($testCases as $testCase) {
            if (is_array($testCase)) {
                // Handle multiple arguments
                $args = array_map(function($arg) {
                    if (is_string($arg)) {
                        return "'" . str_replace("'", "\\'", $arg) . "'";
                    } elseif (is_array($arg)) {
                        return json_encode($arg);
                    }
                    return $arg;
                }, $testCase);
                $testCode .= "results.push($functionName(" . implode(", ", $args) . "));\n";
            } else {
                // Handle single argument
                $arg = is_string($testCase) ? "'" . str_replace("'", "\\'", $testCase) . "'" : $testCase;
                $testCode .= "results.push($functionName($arg));\n";
            }
        }
        
        $testCode .= "console.log(JSON.stringify(results));";
        return $testCode;
    }

    private function generatePhpTestCode($code, $testCases) {
        // Extract function name from the code
        preg_match('/function\s+(\w+)\s*\(/', $code, $matches);
        $functionName = $matches[1] ?? null;
        
        if (!$functionName) {
            return $this->errorResponse("Could not find function definition in code");
        }

        // Remove any PHP opening and closing tags from the input code
        $code = preg_replace('/^\s*<\?php\s*/', '', $code);
        $code = preg_replace('/\?>\s*$/', '', $code);

        // Start with PHP opening tag
        $testCode = "<?php\n";
        $testCode .= $code . "\n\n";
        $testCode .= "\$results = [];\n";
        
        foreach ($testCases as $testCase) {
            if (is_array($testCase)) {
                // Handle multiple arguments
                $args = array_map(function($arg) {
                    if (is_string($arg)) {
                        return "'" . str_replace("'", "\\'", $arg) . "'";
                    } elseif (is_array($arg)) {
                        return json_encode($arg);
                    }
                    return $arg;
                }, $testCase);
                $testCode .= "\$results[] = $functionName(" . implode(", ", $args) . ");\n";
            } else {
                // Handle single argument
                $arg = is_string($testCase) ? "'" . str_replace("'", "\\'", $testCase) . "'" : $testCase;
                $testCode .= "\$results[] = $functionName($arg);\n";
            }
        }
        
        $testCode .= "echo json_encode(\$results);\n";
        return $testCode;
    }

    private function generateJavaTestCode($code, $testCases) {
        // Extract method name from the code
        preg_match('/public\s+static\s+[\w<>]+\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $code, $methodMatches);
        $methodName = $methodMatches[1] ?? null;
        
        if (!$methodName) {
            return $this->errorResponse("Could not find method definition in code");
        }

        // Find the last closing brace of the class
        $lastBracePos = strrpos($code, '}');
        if ($lastBracePos === false) {
            return $this->errorResponse("Invalid class structure");
        }

        // Insert main method before the last closing brace
        $mainMethod = "\n    public static void main(String[] args) {\n";
        $mainMethod .= "        java.util.ArrayList<Object> results = new java.util.ArrayList<>();\n";
        
        foreach ($testCases as $testCase) {
            if (is_array($testCase)) {
                // Handle multiple arguments
                $args = array_map(function($arg) {
                    if (is_string($arg)) {
                        return "\"" . str_replace("\"", "\\\"", $arg) . "\"";
                    } elseif (is_array($arg)) {
                        return "new java.util.ArrayList<>(java.util.Arrays.asList(" . 
                               implode(", ", array_map(function($item) {
                                   return is_string($item) ? "\"$item\"" : $item;
                               }, $arg)) . "))";
                    }
                    return $arg;
                }, $testCase);
                $mainMethod .= "        results.add(" . $methodName . "(" . implode(", ", $args) . "));\n";
            } else {
                // Handle single argument
                $arg = is_string($testCase) ? "\"" . str_replace("\"", "\\\"", $testCase) . "\"" : $testCase;
                $mainMethod .= "        results.add(" . $methodName . "(" . $arg . "));\n";
            }
        }
        
        $mainMethod .= "        System.out.println(results.toString());\n";
        $mainMethod .= "    }\n";

        // Insert the main method before the last closing brace
        $testCode = substr($code, 0, $lastBracePos) . $mainMethod . substr($code, $lastBracePos);
        
        return $testCode;
    }

    /**
     * Compare actual and expected outputs, handling arrays and objects
     */
    private function compareOutputs($actual, $expected) {
        if (!is_array($actual) || !is_array($expected)) {
            return false;
        }

        if (count($actual) !== count($expected)) {
            return false;
        }

        for ($i = 0; $i < count($actual); $i++) {
            $actualVal = $actual[$i];
            $expectedVal = $expected[$i];

            // Handle boolean values
            if (is_bool($actualVal) && is_bool($expectedVal)) {
                if ($actualVal !== $expectedVal) {
                    return false;
                }
                continue;
            }

            // Handle numeric values
            if (is_numeric($actualVal) && is_numeric($expectedVal)) {
                if (abs($actualVal - $expectedVal) > 0.0001) {
                    return false;
                }
                continue;
            }

            // Handle string values
            if ((string)$actualVal !== (string)$expectedVal) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get supported languages from Piston API
     * 
     * @return array
     */
    public function getSupportedLanguages() {
        $ch = curl_init($this->apiUrl . '/runtimes');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get supported languages');
        }

        return json_decode($response, true);
    }

    /**
     * Get file extension for a given language
     * 
     * @param string $language
     * @return string
     */
    private function getFileExtension($language) {
        $extensions = [
            'python' => 'py',
            'python3' => 'py',
            'javascript' => 'js',
            'node' => 'js',
            'typescript' => 'ts',
            'java' => 'java',
            'c' => 'c',
            'cpp' => 'cpp',
            'php' => 'php',
            'ruby' => 'rb',
            'go' => 'go',
            'rust' => 'rs',
            'cs' => 'cs'
        ];

        return $extensions[$language] ?? 'txt';
    }

    /**
     * Standardized error response format
     * 
     * @param string $message
     * @param int|null $code
     * @return array
     */
    private function errorResponse(string $message, ?int $code = null): array {
        return [
            'status' => 'error',
            'message' => $message,
            'code' => $code
        ];
    }

    private function parseOutput($output) {
        // Try to decode as JSON first
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If not JSON, try to parse as Python array
        $output = trim($output, '[]');
        // Split on comma but preserve spaces
        $items = preg_split('/,\s*/', $output);
        return array_map(function($item) {
            if ($item === 'True') return true;
            if ($item === 'False') return false;
            if (is_numeric($item)) return json_decode($item);
            return trim($item, '"\'');
        }, $items);
    }
}
