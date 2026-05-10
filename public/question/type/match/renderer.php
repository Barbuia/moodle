<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Matching question renderer class.
 *
 * @package   qtype_match
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for matching questions.
 *
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_match_renderer extends qtype_with_combined_feedback_renderer {

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        $stemorder = $question->get_stem_order();
        $response = $qa->get_last_qt_data();

        $choices = $this->format_choices($question, $qa);

        $questiontextid = $qa->get_qt_field_name('qtext');
        $result = html_writer::div($question->format_questiontext($qa), 'qtext', ['id' => $questiontextid]);

        $result .= html_writer::start_tag('div', ['class' => 'ablock', 'id' => $qa->get_qt_field_name('ablock')]);
        $result .= html_writer::start_tag('table', ['class' => 'answer table-reboot', 'role' => 'presentation']);
        $result .= html_writer::start_tag('tbody', ['role' => 'presentation']);

        $parity = 0;
        $i = 1;
        foreach ($stemorder as $key => $stemid) {

            $result .= html_writer::start_tag('tr', ['class' => 'r' . $parity, 'role' => 'presentation']);
            $fieldname = 'sub' . $key;

            $itemtextid = $qa->get_qt_field_name($fieldname . '_itemtext');
            $result .= html_writer::tag('td', $this->format_stem_text($qa, $stemid), [
                'class' => 'text',
                'id' => $itemtextid,
                'role' => 'presentation',
            ]);

            $classes = 'control';
            $feedbackimage = '';

            if (array_key_exists($fieldname, $response)) {
                $selected = $response[$fieldname];
            } else {
                $selected = 0;
            }

            $fraction = (int) ($selected && $selected == $question->get_right_choice_for($stemid));

            if ($options->correctness && $selected) {
                $classes .= ' ' . $this->feedback_class($fraction);
                $feedbackimage = $this->feedback_image($fraction);
            }

            // We only want to add the question text to the first answer field to
            // avoid repetition of the question text for the subsequent answer fields.
            if ($i == 1) {
                $ariadescribedbyids = $questiontextid . ' ' . $itemtextid;
            } else {
                $ariadescribedbyids = $itemtextid;
            }

            $labeltext = $options->add_question_identifier_to_label(get_string('answer', 'qtype_match', $i));

            $dropdownhtml = $this->render_choice_dropdown(
                $qa,
                $fieldname,
                $choices,
                (int) $selected,
                $options->readonly,
                $ariadescribedbyids,
                $labeltext,
            );

            $result .= html_writer::tag('td', $dropdownhtml . ' ' . $feedbackimage, [
                'class' => $classes,
                'role' => 'presentation',
            ]);

            $result .= html_writer::end_tag('tr');
            $parity = 1 - $parity;
            $i++;
        }
        $result .= html_writer::end_tag('tbody');
        $result .= html_writer::end_tag('table');

        $result .= html_writer::end_tag('div'); // Closes <div class="ablock">.

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                $question->get_validation_error($response), ['class' => 'validationerror']);
        }

        // Initialise the JavaScript for interactive custom dropdowns.
        if (!$options->readonly) {
            $this->page->requires->js_call_amd(
                'qtype_match/dropdown',
                'init',
                [$qa->get_qt_field_name('ablock'), false],
            );
        }

        return $result;
    }

    /**
     * Render a single custom choice dropdown using the Mustache template.
     *
     * @param question_attempt $qa The question attempt.
     * @param string $fieldname The sub-field name (e.g. 'sub0').
     * @param array $choices Associative array of key => formatted HTML label.
     * @param int $selected The currently selected value.
     * @param bool $disabled Whether the dropdown is disabled (readonly).
     * @param string $ariadescribedby Space-separated element IDs for aria-describedby.
     * @param string $accesshidelabel The accessible label text.
     * @return string The rendered HTML.
     */
    protected function render_choice_dropdown(
        question_attempt $qa,
        string $fieldname,
        array $choices,
        int $selected,
        bool $disabled,
        string $ariadescribedby,
        string $accesshidelabel,
    ): string {
        $chooselabel = get_string('choosedots');
        $selectedlabel = $selected ? ($choices[$selected] ?? $chooselabel) : $chooselabel;

        $templateoptions = [];
        $templateoptions[] = [
            'value' => 0,
            'label' => $chooselabel,
            'selected' => ($selected == 0),
        ];
        foreach ($choices as $value => $label) {
            $templateoptions[] = [
                'value' => $value,
                'label' => $label,
                'selected' => ($value == $selected),
            ];
        }

        $context = [
            'fieldname' => $qa->get_qt_field_name($fieldname),
            'fieldid' => 'menu_' . str_replace(':', '_', $qa->get_qt_field_name($fieldname)),
            'selectedvalue' => $selected,
            'selectedlabel' => $selectedlabel,
            'disabled' => $disabled,
            'ariadescribedby' => $ariadescribedby,
            'accesshidelabel' => $accesshidelabel,
            'options' => $templateoptions,
        ];

        return $this->output->render_from_template('qtype_match/choice_dropdown', $context);
    }

    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    /**
     * Format each question stem. Overwritten by randomsamatch renderer.
     *
     * @param question_attempt $qa
     * @param integer $stemid stem index
     * @return string
     */
    public function format_stem_text($qa, $stemid) {
        $question = $qa->get_question();
        return $question->format_text(
            $question->stems[$stemid], $question->stemformat[$stemid],
            $qa, 'qtype_match', 'subquestion', $stemid);
    }

    /**
     * Format the choices for display, applying full Moodle text filters including MathJax.
     *
     * @param object $question The question object.
     * @param question_attempt|null $qa The question attempt (needed for filter context).
     * @return array Associative array of choice key => formatted HTML string.
     */
    protected function format_choices($question, ?question_attempt $qa = null) {
        $choices = [];
        foreach ($question->get_choice_order() as $key => $choiceid) {
            if ($qa !== null) {
                // Use format_text via the question object so all Moodle text filters
                // (MathJax, multilang, etc.) are applied. Choices are stored as plain text,
                // so FORMAT_PLAIN is the correct format constant.
                $choices[$key] = $question->format_text(
                    $question->choices[$choiceid],
                    FORMAT_PLAIN,
                    $qa,
                    'qtype_match',
                    'subquestion',
                    null,
                );
            } else {
                // Fallback when no question attempt is available.
                $choices[$key] = format_text(
                    $question->choices[$choiceid],
                    FORMAT_PLAIN,
                );
            }
        }
        return $choices;
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $stemorder = $question->get_stem_order();

        $choices = $this->format_choices($question, $qa);
        $right = [];
        foreach ($stemorder as $key => $stemid) {
            if (!isset($choices[$question->get_right_choice_for($stemid)])) {
                continue;
            }
            $right[] = $question->make_html_inline($this->format_stem_text($qa, $stemid)) .
                ' &#x2192; ' .
                $question->make_html_inline($choices[$question->get_right_choice_for($stemid)]);
        }

        if (!empty($right)) {
            return get_string('correctansweris', 'qtype_match', implode(', ', $right));
        }
        return '';
    }
}
