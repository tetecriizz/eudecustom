@local @local_eudedashboard @local_eudedashboard_manager @javascript
Feature: View my Eudedashboard custom dashboard
    In order to see my Eudedashboard custom dashboard
    As an admin
    I need to be able to view dashboard

  Scenario: Check access capability by configuration
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | 1        | user1@example.com    |
      | user2    | User      | 2        | user2@example.com    |
      | user3    | User      | 3        | user3@example.com    |
    And the following "categories" exist:
      | name     | idnumber |  category |
      | Programs | Programs |  0        |
      | Program1 | Program1 |  Programs |
      | Program2 | Program2 |  Programs |
      | Edition1 | Edition1 |  Program1 |
      | Edition2 | Edition2 |  Program1 |
      | Edition3 | Edition3 |  Program1 |

    And the following "courses" exist:
      | category | shortname           | fullname            | idnumber          |
      | Edition1 | Course1ofedition1   | Course1ofedition1   | Course1ofedition1 |
      | Edition1 | Course2ofedition1   | Course2ofedition1   | Course2ofedition1 |
      | Edition1 | Course3ofedition1   | Course3ofedition1   | Course3ofedition1 |
      | Edition2 | Course1ofedition2   | Course1ofedition2   | Course1ofedition2 |
      | Edition2 | Course2ofedition2   | Course2ofedition2   | Course2ofedition2 |
      | Edition2 | Course3ofedition2   | Course3ofedition2   | Course3ofedition2 |
      | Edition3 | Course1ofedition3   | Course1ofedition3   | Course1ofedition3 |
      | Edition3 | Course2ofedition3   | Course2ofedition3   | Course2ofedition3 |
      | Edition3 | Course3ofedition3   | Course3ofedition3   | Course3ofedition3 |

    And the following "course enrolments" exist:
      | user     | course            | role    |
      | user1    | Course1ofedition1 | student |
      | user1    | Course2ofedition1 | student |
      | user1    | Course3ofedition1 | student |
      | user1    | Course1ofedition2 | student |
      | user1    | Course2ofedition2 | student |
      | user1    | Course3ofedition2 | student |
      | user1    | Course1ofedition3 | student |
      | user1    | Course2ofedition3 | student |
      | user1    | Course3ofedition3 | student |
      | user2    | Course1ofedition1 | student |
      | user2    | Course2ofedition1 | student |
      | user2    | Course3ofedition1 | student |
      | user2    | Course1ofedition2 | student |
      | user2    | Course2ofedition2 | student |
      | user2    | Course3ofedition2 | student |
      | user2    | Course1ofedition3 | student |
      | user2    | Course2ofedition3 | student |
      | user2    | Course3ofedition3 | student |
      | user3    | Course1ofedition1 | teacher |
      | user3    | Course2ofedition1 | teacher |
      | user3    | Course3ofedition1 | teacher |
      | user3    | Course1ofedition2 | teacher |
      | user3    | Course2ofedition2 | teacher |
      | user3    | Course3ofedition2 | teacher |
      | user3    | Course1ofedition3 | teacher |
      | user3    | Course2ofedition3 | teacher |
      | user3    | Course3ofedition3 | teacher |

    # coursecreator has not an authorized access to
    # manager dashboard and will be redirected to home page
    And I log in as "user3"
    And I go to eudedashboard main
    And I wait to be redirected
    Then I log out

    # Access as admin to enable cat1 and cat2 and add role for coursecreator to manager dashboard
    And I log in as "admin"
    And I go to eudedashboard configuration
    # Option 1 is selected by default, must enable Cat1 and Cat2
    And I click on "//select[@id='id_s__local_eudedashboard_category']/option[2]" "xpath_element"
    # Enable coursecreator as authorized role to see manager dashboard
    And I click on "//select[@id='id_s__local_eudedashboard_role']/option[5]" "xpath_element"
    And I click on "//form[@id='adminsettings']//button[@type='submit']" "xpath_element"
    And I go to eudedashboard main

    #Check that have 1 teacher, 2 students and 9 modules in programs row
    And I should see "1" in the "//table[@id='local_eudedashboard_datatable']/tbody/tr[2]/td[3]" "xpath_element"
    And I should see "2" in the "//table[@id='local_eudedashboard_datatable']/tbody/tr[2]/td[4]" "xpath_element"
    And I should see "9" in the "//table[@id='local_eudedashboard_datatable']/tbody/tr[2]/td[5]" "xpath_element"

    #Click on the first edition
    And I click on "//table[@id='local_eudedashboard_datatable']/tbody/tr[2]/td[1]" "xpath_element"
    And I click on "//tr[@class='eude-trsubrows'][1]/td[3]/a" "xpath_element"
    And I wait to be redirected

    #Check that information teacher is correct
    And I should see "1 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[1]/a" "xpath_element"
    And I should see "2 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[2]/a" "xpath_element"
    And I should see "3 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[3]/a" "xpath_element"

    #Check that information student is correct
    And I should see "1 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[1]/a" "xpath_element"
    And I should see "2 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[2]/a" "xpath_element"
    And I should see "3 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[3]/a" "xpath_element"

    #Check that information modules is correct
    And I should see "1 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[1]/a" "xpath_element"
    And I should see "2 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[2]/a" "xpath_element"
    And I should see "3 " in the "//div[@class='table-responsive-sm eude-table-header']/div[2]/div[3]/a" "xpath_element"

    #Go to the information of module
    And I click on "//table[@id='local_eudedashboard_datatable']/tbody/tr[1]/td[1]" "xpath_element"

    #Go to de studentinfo
    And I go to eudedashboard main
    And I click on "//table[@id='local_eudedashboard_datatable']/tbody/tr[2]/td[1]" "xpath_element"
    And I click on "//tr[@class='eude-trsubrows'][1]/td[4]/a" "xpath_element"
    And I wait to be redirected
    And I click on "//table[@id='local_eudedashboard_datatable']/tbody/tr[1]/td[1]" "xpath_element"
    #Click on Modules tab
    And I click on "//div[@class='dashboard-row']//div[@class='list-tabs']/a[2]" "xpath_element"

    #Go to the teacherinfo
    And I go to eudedashboard main
    And I click on "//table[@id='local_eudedashboard_datatable']/tbody/tr[2]/td[1]" "xpath_element"
    And I click on "//tr[@class='eude-trsubrows'][1]/td[3]/a" "xpath_element"
    And I wait to be redirected
    And I click on "//table[@id='local_eudedashboard_datatable']/tbody/tr[1]/td[1]" "xpath_element"
    #Click on Modules tab
    And I click on "//div[@class='dashboard-row']//div[@class='list-tabs']/a[2]" "xpath_element"
