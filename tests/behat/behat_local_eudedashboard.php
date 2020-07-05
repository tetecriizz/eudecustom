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
 * Behat steps definition
 *
 * @package    local_eudedashboard
 * @category   test
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given;
use Behat\Behat\Context\Step\When as When;

/**
 * Class that contains helper functions.
 *
 * @package    local_eudedashboard
 * @category   test
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_eudedashboard extends behat_base {

    /**
     * Opens Eude dashboard page.
     *
     * @Given /^I go to eudedashboard$/
     */
    public function i_go_to_eudedashboard() {
        $this->getSession()->visit($this->locate_path("/local/eudedashboard/eudedashboard.php"));
    }

    /**
     * Opens eudedashboard configuration.
     *
     * @Given /^I go to eudedashboard configuration$/
     */
    public function i_go_to_eudedashboard_configuration () {
        $this->getSession()->visit($this->locate_path("/admin/settings.php?section=local_eudedashboard"));
    }
}
