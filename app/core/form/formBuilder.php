<?php
namespace App\Core\Form;

// FormBuilder class to dynamically create forms
class FormBuilder {
    private $formFields = [];
    private $action;
    private $method;
    private $ajax;
    private $errors;
    private $formData;

    // Constructor to initialize form properties
    public function __construct($action = "", $method = "post", $ajax = false, $errors = [], $formData = []) {
        $this->action = $action;
        $this->method = $method;
        $this->ajax = $ajax;
        $this->errors = $errors;
        $this->formData = $formData;
    }

    // Function to add fields to the form
    public function addField($id, $type, $label, $validators = [], $options = []) {
        $this->formFields[] = [
            'id' => $id,
            'type' => $type,
            'label' => $label,
            'validators' => $validators,
            'options' => $options
        ];
        return $this;
    }

    // Function to build the form
    public function buildForm() {
        $form = "<form action='{$this->action}' method='{$this->method}' novalidate";
        if ($this->ajax) {
            $form .= " data-ajax='true'"; // Enable AJAX submission
        }
        $form .= ">";

        foreach ($this->formFields as $field) {
            $form .= $this->buildField($field);
        }

        $form .= "<button type='submit' class='btn btn-outline-secondary mt-3'>Submit</button>";
        $form .= "</form>";

        return $form;
    }

    // Function to build individual fields with Bootstrap styles
    private function buildField($field) {
        $html = "<div class='mb-3'>"; // Bootstrap spacing

        $html .= "<label for='{$field['id']}' class='form-label'>{$field['label']}</label>";

        // Get server-side validation errors
        $error = $this->errors[$field['id']] ?? '';
        $errorClass = $error ? 'is-invalid' : '';

        // Extract user input (retained after validation failure)
        $value = htmlspecialchars($this->formData[$field['id']] ?? '');
        $validatorsAttr = json_encode($field['validators']);
        $events = "oninput='validator.validate(this, {$validatorsAttr})' onblur='validator.validate(this, {$validatorsAttr})'";

        // Build the field based on its type
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'password':
            case 'number':
            case 'tel':
                $html .= "<input type='{$field['type']}' id='{$field['id']}' name='{$field['id']}' value='{$value}' class='form-control {$errorClass}' {$events} required>";
                break;
            case 'textarea':
                $html .= "<textarea id='{$field['id']}' name='{$field['id']}' class='form-control {$errorClass}' {$events} required>{$value}</textarea>";
                break;
            case 'select':
                $html .= "<select id='{$field['id']}' name='{$field['id']}' class='form-select {$errorClass}' {$events} required>";
                $html .= "<option value='default'>-- Select --</option>";
                foreach ($field['options'] as $option) {
                    $selected = ($option == $value) ? "selected" : "";
                    $html .= "<option value='{$option}' {$selected}>{$option}</option>";
                }
                $html .= "</select>";
                break;
            case 'radio':
                foreach ($field['options'] as $option) {
                    $checked = ($option == $value) ? "checked" : "";
                    $html .= "<div class='form-check'>";
                    $html .= "<input class='form-check-input' type='radio' name='{$field['id']}' value='{$option}' {$checked} {$events}>";
                    $html .= "<label class='form-check-label'>{$option}</label>";
                    $html .= "</div>";
                }
                break;
            case 'checkbox':
                $checked = ($value == "on") ? "checked" : "";
                $html .= "<div class='form-check'>";
                $html .= "<input class='form-check-input' type='checkbox' id='{$field['id']}' name='{$field['id']}' {$checked} {$events}>";
                $html .= "<label class='form-check-label' for='{$field['id']}'>{$field['label']}</label>";
                $html .= "</div>";
                break;
        }

        // Display server-side error message
        if ($error) {
            $html .= "<div class='invalid-feedback'>{$error}</div>";
        }

        $html .= "</div>";

        return $html;
    }   
}    
?>
