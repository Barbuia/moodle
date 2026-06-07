@mod @mod_quiz @core_question @javascript
Feature: Datafilter loads when adding a random question to a quiz
  In order to add random questions to a quiz
  As a teacher
  I need the question bank filter UI to initialise without JavaScript errors

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name   | course | idnumber |
      | quiz     | Quiz 1 | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory | qtype     | name            |
      | Default for C1   | truefalse | First question  |
      | Default for C1   | truefalse | Second question |

  Scenario: The add-random-question dialog opens without breaking the filter widget
    Given I am on the "Quiz 1" "mod_quiz > edit" page logged in as "teacher1"
    When I open the action menu in "Add" "button"
    And I choose "a random question" in the open action menu
    Then I should see "Add a random question"
    And "Apply filters" "button" should exist
