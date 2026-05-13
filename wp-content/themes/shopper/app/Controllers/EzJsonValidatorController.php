<?php

namespace App\Controllers;

class EzJsonValidatorController
{
    /**
     * Validate JSON data
     * @param WP_REST_Request $request
     * @return array
     */
    public function validateJson(\WP_REST_Request $request)
    {
        try {
            // First try to get JSON from file upload
            $files = $request->get_file_params();
            $raw_data = '';

            if (!empty($files['json_file']) && !empty($files['json_file']['tmp_name'])) {
                $raw_data = file_get_contents($files['json_file']['tmp_name']);
            } else {
                // If no file, try to get JSON from request body
                $raw_data = $request->get_body();
            }

            // Debug information about the request
            if (empty($raw_data)) {
                return [
                    'valid' => false,
                    'message' => 'No JSON data provided',
                    'debug' => [
                        'method' => $request->get_method(),
                        'content_type' => $request->get_header('content-type'),
                        'has_files' => !empty($files),
                        'files' => $files,
                        'body_length' => strlen($request->get_body()),
                        'raw_params' => $request->get_params()
                    ]
                ];
            }

            // Try to decode JSON with detailed error handling
            $data = json_decode($raw_data, true);
            $json_error = json_last_error();
            
            if ($json_error !== JSON_ERROR_NONE) {
                $error_message = 'JSON Error: ';
                switch ($json_error) {
                    case JSON_ERROR_DEPTH:
                        $error_message .= 'Maximum stack depth exceeded';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $error_message .= 'Invalid or malformed JSON';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $error_message .= 'Control character error';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $error_message .= 'Syntax error - malformed JSON';
                        break;
                    case JSON_ERROR_UTF8:
                        $error_message .= 'Malformed UTF-8 characters';
                        break;
                    default:
                        $error_message .= json_last_error_msg();
                }

                // Debug information
                return [
                    'valid' => false,
                    'message' => $error_message,
                    'debug' => [
                        'raw_length' => strlen($raw_data),
                        'first_chars' => substr($raw_data, 0, 100) . '...',
                        'error_code' => $json_error,
                        'method' => $request->get_method(),
                        'content_type' => $request->get_header('content-type')
                    ]
                ];
            }

            // Validate the data structure
            $validation = $this->validateData($data);
            return [
                'valid' => $validation['valid'],
                'message' => $validation['message'],
                'errors' => $validation['errors'] ?? []
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'debug' => [
                    'method' => $request->get_method(),
                    'content_type' => $request->get_header('content-type'),
                    'has_files' => !empty($files)
                ]
            ];
        }
    }

    /**
     * Validate data structure
     * @param array $data
     * @return array
     */
    private function validateData($data)
    {
        $errors = [];

        // Check if products array exists
        if (!isset($data['products']) || !is_array($data['products'])) {
            return [
                'valid' => false,
                'message' => 'Missing or invalid products array'
            ];
        }

        foreach ($data['products'] as $index => $product) {
            $productErrors = [];

            // Required fields
            if (empty($product['name'])) {
                $productErrors[] = 'Missing product name';
            }

            // Validate categories
            if (!empty($product['categories'])) {
                if (!is_array($product['categories'])) {
                    $productErrors[] = 'Categories must be an array';
                } else {
                    foreach ($product['categories'] as $catIndex => $category) {
                        $categoryErrors = $this->validateCategory($category);
                        if (!empty($categoryErrors)) {
                            $productErrors[] = "Category at index {$catIndex}: " . implode(', ', $categoryErrors);
                        }
                    }
                }
            }

            // Validate attributes
            if (!empty($product['attributes'])) {
                if (!is_array($product['attributes'])) {
                    $productErrors[] = 'Attributes must be an array';
                } else {
                    foreach ($product['attributes'] as $attrIndex => $attribute) {
                        if (empty($attribute['name'])) {
                            $productErrors[] = "Attribute at index {$attrIndex} missing name";
                        }
                        if (!isset($attribute['options']) || !is_array($attribute['options'])) {
                            $productErrors[] = "Attribute at index {$attrIndex} missing or invalid options array";
                        }
                    }
                }
            }

            // Validate variations
            if (!empty($product['variations'])) {
                if (!is_array($product['variations'])) {
                    $productErrors[] = 'Variations must be an array';
                } else {
                    foreach ($product['variations'] as $varIndex => $variation) {
                        if (empty($variation['attributes']) || !is_array($variation['attributes'])) {
                            $productErrors[] = "Variation at index {$varIndex} missing or invalid attributes";
                        }
                    }
                }
            }

            // Validate SEO data
            if (!empty($product['seo'])) {
                $seoErrors = $this->validateSeoData($product['seo']);
                if (!empty($seoErrors)) {
                    $productErrors = array_merge($productErrors, $seoErrors);
                }
            }

            // Add product errors if any
            if (!empty($productErrors)) {
                $errors["product_{$index}"] = $productErrors;
            }
        }

        // Validate coupons if present
        if (isset($data['coupons'])) {
            if (!is_array($data['coupons'])) {
                $errors['coupons'] = 'Coupons must be an array';
            } else {
                foreach ($data['coupons'] as $index => $coupon) {
                    $couponErrors = $this->validateCoupon($coupon);
                    if (!empty($couponErrors)) {
                        $errors["coupon_{$index}"] = $couponErrors;
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Validation successful' : 'Validation failed',
            'errors' => $errors
        ];
    }

    /**
     * Validate category data
     * @param array $category
     * @return array
     */
    private function validateCategory($category)
    {
        $errors = [];

        // If ID is provided, check if it exists
        if (!empty($category['id'])) {
            $term = get_term($category['id'], 'product_cat');
            if (!$term || is_wp_error($term)) {
                $errors[] = "Category ID {$category['id']} does not exist";
            }
        }

        // If no ID, name is required
        if (empty($category['id']) && empty($category['name'])) {
            $errors[] = 'Category requires either ID or name';
        }

        // Validate parent if provided
        if (!empty($category['parent'])) {
            $parent_term = get_term($category['parent'], 'product_cat');
            if (!$parent_term || is_wp_error($parent_term)) {
                $errors[] = "Parent category ID {$category['parent']} does not exist";
            }
        }

        // Validate image URL if provided
        if (!empty($category['image'])) {
            if (!filter_var($category['image'], FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid image URL';
            }
        }

        return $errors;
    }

    /**
     * Validate SEO data
     * @param array $seo
     * @return array
     */
    private function validateSeoData($seo)
    {
        $errors = [];

        $seoFields = [
            'meta_title' => 60,
            'meta_description' => 160,
            'focus_keywords' => 100,
            'canonical_url' => 2048,
            'redirect_to' => 2048
        ];

        foreach ($seoFields as $field => $maxLength) {
            if (!empty($seo[$field]) && strlen($seo[$field]) > $maxLength) {
                $errors[] = "{$field} exceeds maximum length of {$maxLength} characters";
            }
        }

        if (!empty($seo['canonical_url']) && !filter_var($seo['canonical_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid canonical URL';
        }

        if (!empty($seo['redirect_to']) && !filter_var($seo['redirect_to'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid redirect URL';
        }

        return $errors;
    }

    /**
     * Validate coupon data
     * @param array $coupon
     * @return array
     */
    private function validateCoupon($coupon)
    {
        $errors = [];

        if (empty($coupon['code'])) {
            $errors[] = 'Missing coupon code';
        }

        if (!empty($coupon['amount']) && !is_numeric($coupon['amount'])) {
            $errors[] = 'Invalid coupon amount';
        }

        if (!empty($coupon['expiry_date'])) {
            if (!strtotime($coupon['expiry_date'])) {
                $errors[] = 'Invalid expiry date format';
            }
        }

        return $errors;
    }
} 