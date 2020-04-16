<?php
    namespace App\Models;
    class Helper {

        /**
         * Checks if nickname starts with letter and continues with letter or number
         */
        public function isValidNickname($name) {
            return preg_match_all("/^[a-zA-ZäüößÄÜÖ]+[a-zA-Z0-9äöüÄÖÜß]$/", $name) == 1;
        }
    }

?>