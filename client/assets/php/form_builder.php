<?php
/**
 * Form Builder Utility
 * 
 * A utility class for generating consistent form components across the application.
 * Usage:
 * $form = new FormBuilder();
 * echo $form->build([
 *     'title' => 'Login Form',
 *     'action' => '/login',
 *     'method' => 'POST',
 *     'fields' => [
 *         ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
 *         ['type' => 'password', 'name' => 'password', 'label' => 'Password', 'required' => true]
 *     ],
 *     'submitText' => 'Login',
 *     'submitIcon' => 'fas fa-sign-in-alt'
 * ]);
 */

class FormBuilder {
    private $formDefaults = [
        'title' => '',
        'action' => '#',
        'method' => 'POST',
        'fields' => [],
        'submitText' => 'Submit',
        'submitIcon' => '',
        'submitClasses' => '',
        'formClasses' => '',
        'formId' => '',
        'formAttributes' => '',
        'enctype' => 'application/x-www-form-urlencoded',
        'icon' => '',
        'classes' => '',
        'footer' => ''
    ];

    public function build($options = []) {
        $options = array_merge($this->formDefaults, $options);
        
        // Add CSS styles for form controls
        $formContent = <<<EOT
<style>
/* Form container */
.form-container {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    padding: 1.5rem;
    margin: 0 auto;
    width: 100%;
    max-width: 600px;
}

/* Form header */
.form-header {
    margin-bottom: 1.5rem;
    text-align: center;
}

.form-icon {
    color: var(--primary-color);
    font-size: 2rem;
    margin-bottom: 1rem;
}

.form-title {
    color: var(--text-color);
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

/* Form controls */
.form-control,
.form-select {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
    width: 100%;
    transition: border-color 0.2s ease-in-out;
}

.form-control:hover,
.form-select:hover {
    border-color: var(--primary-color);
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: none;
}

.form-label {
    color: var(--text-color);
    margin-bottom: 0.5rem;
    display: block;
}

/* Button */
.btn-primary,
.btn-login,
.btn-register {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    border: none !important;
    padding: 0.75rem 2rem !important;
    font-size: 1rem !important;
    border-radius: 0.5rem !important;
    color: #fff !important;
    width: 100% !important;
    transition: background-color 0.3s !important;
}

.btn-primary:hover,
.btn-login:hover,
.btn-register:hover {
    background-color: color-mix(in srgb, var(--primary-color) 90%, white 10%) !important;
    border-color: color-mix(in srgb, var(--primary-color) 90%, white 10%) !important;
}

.btn-primary:focus,
.btn-login:focus,
.btn-register:focus {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 0.25rem rgba(16, 163, 127, 0.25) !important;
}

.btn-primary:active,
.btn-login:active,
.btn-register:active {
    background-color: color-mix(in srgb, var(--primary-color) 90%, black 10%) !important;
    border-color: color-mix(in srgb, var(--primary-color) 90%, black 10%) !important;
}

/* Form footer */
.form-footer {
    margin-top: 1.5rem;
    text-align: center;
    color: var(--text-color);
}

/* Validation */
.form-control.is-valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.error-message {
    display: none;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.error-message.visible {
    display: block;
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .form-container {
        border-radius: 0;
        border-left: none;
        border-right: none;
        max-width: 100%;
        height: 100vh;
        padding: 1rem;
        display: flex;
        flex-direction: column;
    }

    .form-container form {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .form-container .mt-4 {
        margin-top: auto;
    }
}
</style>
EOT;

        // Build the form container
        $formContent .= '<div class="form-container ' . $options['classes'] . '">';
        
        // Add header with icon and title if provided
        if ($options['icon'] || $options['title']) {
            $formContent .= '<div class="form-header">';
            if ($options['icon']) {
                if (strpos($options['icon'], '<i') === 0) {
                    $formContent .= '<div class="form-icon">' . $options['icon'] . '</div>';
                } else {
                    $formContent .= '<div class="form-icon"><i class="' . $options['icon'] . '"></i></div>';
                }
            }
            if ($options['title']) {
                $formContent .= '<h3 class="form-title">' . $options['title'] . '</h3>';
            }
            $formContent .= '</div>';
        }

        // Build the form
        $formContent .= '<form action="' . $options['action'] . '" 
                            method="' . $options['method'] . '" 
                            class="' . $options['formClasses'] . '"
                            enctype="' . $options['enctype'] . '"' .
                            ($options['formId'] ? ' id="' . $options['formId'] . '"' : '') .
                            ($options['formAttributes'] ? ' ' . $options['formAttributes'] : '') . '>';
        
        foreach ($options['fields'] as $field) {
            $formContent .= $this->buildField($field);
        }

        // Add submit button
        $formContent .= '<div class="mt-4">';
        $formContent .= '<button type="submit" class="btn btn-primary w-100 ' . $options['submitClasses'] . '">';
        if ($options['submitIcon']) {
            $formContent .= '<i class="' . $options['submitIcon'] . ' me-2"></i>';
        }
        $formContent .= $options['submitText'];
        $formContent .= '</button>';
        $formContent .= '</div>';

        $formContent .= '</form>';

        // Add footer if provided
        if ($options['footer']) {
            $formContent .= '<div class="form-footer">' . $options['footer'] . '</div>';
        }

        $formContent .= '</div>';

        return $formContent;
    }

    private function buildField($field) {
        $field = array_merge([
            'type' => 'text',
            'name' => '',
            'id' => '',
            'label' => '',
            'value' => '',
            'placeholder' => '',
            'required' => false,
            'classes' => '',
            'help' => '',
            'options' => [],
            'validators' => [],
            'min' => null,
            'max' => null
        ], $field);

        if (empty($field['id'])) {
            $field['id'] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $field['name']));
        }

        $html = '<div class="form-field mb-4">';
        
        if ($field['label']) {
            $html .= '<label for="' . $field['id'] . '" class="form-label">' . $field['label'];
            if ($field['required']) {
                $html .= ' <span class="text-danger">*</span>';
            }
            $html .= '</label>';
        }

        switch ($field['type']) {
            case 'select':
                $html .= $this->buildSelect($field);
                break;
            case 'textarea':
                $html .= $this->buildTextarea($field);
                break;
            case 'checkbox':
            case 'radio':
                $html .= $this->buildCheckboxRadio($field);
                break;
            default:
                $html .= $this->buildInput($field);
        }

        // Add error message container with fixed height
        $html .= '<div class="error-message-container" style="height: 1.5rem;">';
        $html .= '<div class="error-message"></div>';
        $html .= '</div>';

        if ($field['help']) {
            $html .= '<div class="form-text">' . $field['help'] . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    private function buildInput($field) {
        $validationAttrs = '';
        if ($field['required']) {
            $validationAttrs .= ' required';
        }
        if ($field['min'] !== null) {
            $validationAttrs .= ' minlength="' . $field['min'] . '"';
        }
        if ($field['max'] !== null) {
            $validationAttrs .= ' maxlength="' . $field['max'] . '"';
        }

        return '<input type="' . $field['type'] . '" 
                      class="form-control ' . $field['classes'] . '" 
                      id="' . $field['id'] . '" 
                      name="' . $field['name'] . '" 
                      value="' . htmlspecialchars($field['value']) . '" 
                      placeholder="' . htmlspecialchars($field['placeholder']) . '" 
                      ' . $validationAttrs . '>';
    }

    private function buildTextarea($field) {
        return '<textarea class="form-control ' . $field['classes'] . '" 
                         id="' . $field['id'] . '" 
                         name="' . $field['name'] . '" 
                         placeholder="' . htmlspecialchars($field['placeholder']) . '" 
                         ' . ($field['required'] ? 'required' : '') . '>' . 
                htmlspecialchars($field['value']) . '</textarea>';
    }

    private function buildSelect($field) {
        $html = '<select class="form-select ' . $field['classes'] . '" 
                        id="' . $field['id'] . '" 
                        name="' . $field['name'] . '" 
                        ' . ($field['required'] ? 'required' : '') . '>';
        
        if ($field['placeholder']) {
            $html .= '<option value="">' . htmlspecialchars($field['placeholder']) . '</option>';
        }

        foreach ($field['options'] as $value => $label) {
            $selected = $value == $field['value'] ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . 
                    htmlspecialchars($label) . '</option>';
        }

        $html .= '</select>';
        return $html;
    }

    private function buildCheckboxRadio($field) {
        $html = '';
        foreach ($field['options'] as $value => $label) {
            $checked = $value == $field['value'] ? ' checked' : '';
            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input" 
                            type="' . $field['type'] . '" 
                            name="' . $field['name'] . '" 
                            id="' . $field['id'] . '_' . $value . '" 
                            value="' . htmlspecialchars($value) . '"' . 
                            $checked . '>';
            $html .= '<label class="form-check-label" for="' . $field['id'] . '_' . $value . '">' . 
                    htmlspecialchars($label) . '</label>';
            $html .= '</div>';
        }
        return $html;
    }

    public function buildCentered($options = []): string {
        $options['classes'] = isset($options['classes']) ? 
            $options['classes'] . ' text-center' : 
            'text-center';
        return $this->build($options);
    }
} 