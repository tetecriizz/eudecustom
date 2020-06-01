@local @local_eudecustom @javascript
Feature: View my custom dashboard
    In order to see my custom dashboard
    As a user student or as a user teacher
    I want to navigate into the system

      
Scenario: Check access capability by configuration
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | 1        | user1@example.com    |
      | user2    | User      | 2        | user2@example.com    |
      | user3    | User      | 3        | user3@example.com    |
    And the following "categories" exist:
      | name | idnumber |
      | Cat1 | CAT1     |
      | Cat2 | CAT2     |
    And the following "courses" exist:
      | category | shortname   | fullname    | idnumber |
      | CAT1     | CAT1.M.HS   | CAT1.M.HS   | C1       |
      | CAT1     | CAT1.M.GS   | CAT1.M.GS   | C2       |
      | CAT2     | MI.GYH      | MI.GYH      | C3       |
      | CAT2     | CAT2.M.TT   | CAT2.M.TT   | C4       |
    And the following "course enrolments" exist:
      | user     | course         | role           | timestart        | timeend       |
      | user1    | CAT1.M.HS      | editingteacher | 1389081600       | 0             |
      | user1    | CAT1.M.GS      | editingteacher | 1389081600       | 0             |
      | user1    | MI.GYH         | editingteacher | 1389081600       | 0             |
      | user1    | CAT2.M.TT      | editingteacher | 1389081600       | 0             |
      | user2    | CAT1.M.HS      | student        | 1496268000       | 0             |
      | user2    | CAT1.M.GS      | student        | 1512082860       | 0             |
      | user2    | MI.GYH         | student        | 1496268000       | 0             |
      | user2    | CAT2.M.TT      | student        | 1496268000       | 0             |
      | user3    | CAT2.M.TT      | coursecreator  | 1389081600       | 0             |
    
    # coursecreator has not an authorized access to 
    # manager dashboard and will be redirected to home page
    And I log in as "user3"
    And I go to eudedashboard
    And I wait to be redirected
    Then I log out

    # Access as admin to enable cat1 and cat2 and add role for coursecreator to manager dashboard
    And I log in as "admin"
    And I go to eudecustom configuration
    # Option 1 is selected by default, must enable Cat1 and Cat2
    And I click on "//select[@id='id_s__local_eudecustom_category']/option[2]" "xpath_element"
    And I click on "//select[@id='id_s__local_eudecustom_category']/option[3]" "xpath_element"
    # Enable coursecreator as authorized role to see manager dashboard
    And I click on "//select[@id='id_s__local_eudecustom_role']/option[1]" "xpath_element"
    And I click on "//form[@id='adminsettings']//button[@type='submit']" "xpath_element"
    And I go to eudedashboard
    Then I log out

    # Now has an authorized role to see manager dashboard
    And I log in as "user3"
    And I go to eudedashboard
    # Click button "teachers" to be redirected to teacher list
    And I click on "//table[@id='local_eudecustom_datatable']//tbody//tr[2]/td[2]/a" "xpath_element"
    And I wait to be redirected

    # Teacher list page
    And I should see "Cat1" in the "//div[@class='table-responsive-sm eude-table-header']/table/tbody/tr[1]/td[1]" "xpath_element"
    And I should see "User 1" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[2]" "xpath_element"
    # View detail of result
    And I click on "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[6]/a" "xpath_element"
    And I wait to be redirected
    And I should see "CAT1.M.HS" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[2]" "xpath_element"
    And I should see "CAT1.M.GS" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[2]/td[2]" "xpath_element"
    # Return to teacher list page
    And I click on "//div[@class='report-header-box']/span/a" "xpath_element"
    And I wait to be redirected

    # Click button "students" to be redirected to student list
    And I click on "//div[@class='table-responsive-sm eude-table-header']/table/tbody/tr[1]/td[3]" "xpath_element"
    And I wait to be redirected

    # Student list page
    And I should see "Cat1" in the "//div[@class='table-responsive-sm eude-table-header']/table/tbody/tr[1]/td[1]" "xpath_element"
    And I should see "User 2" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[2]" "xpath_element"
    # View detail of result
    And I click on "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[7]/a" "xpath_element"
    And I wait to be redirected
    And I should see "CAT1.M.HS" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[2]" "xpath_element"
    And I should see "CAT1.M.GS" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[2]/td[2]" "xpath_element"
    # Return to teacher list page
    And I click on "//div[@class='report-header-box']/span/a" "xpath_element"
    And I wait to be redirected

    # Click button "courses" to be redirected to course list
    And I click on "//div[@class='table-responsive-sm eude-table-header']/table/tbody/tr[1]/td[4]/a" "xpath_element"
    And I wait to be redirected

    # Course list page
    And I should see "Cat1" in the "//div[@class='table-responsive-sm eude-table-header']/table/tbody/tr[1]/td[1]" "xpath_element"
    And I should see "CAT1.M.HS" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[2]" "xpath_element"
    And I should see "CAT1.M.GS" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[2]/td[2]" "xpath_element"
    # View detail of result
    And I click on "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[6]/a" "xpath_element"
    And I wait to be redirected
    And I should see "User 2" in the "//table[@id='local_eudecustom_datatable']/tbody/tr[1]/td[2]" "xpath_element"
    # Return to teacher list page
    And I click on "//div[@class='report-header-box']/span/a" "xpath_element"
    And I wait to be redirected

    # Go back to the main dashboard
    And I click on "//div[@class='report-header-box']/span/a" "xpath_element"
    And I wait to be redirected
    Then I log out