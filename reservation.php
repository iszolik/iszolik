<?php

class Reservation {
    protected $bank_account_number;

    public function save() {
        // Validate the bank account number
        if ($this->validateBankAccountNumber($this->bank_account_number)) {
            // Save bank account number logic here
        } else {
            throw new Exception('Invalid bank account number.');
        }
    }

    public function finalize() {
        // Logic for finalizing any reservation
        // Save any other necessary data here
    }

    protected function validateBankAccountNumber($number) {
        // Implement validation logic for the bank account number
        return preg_match('/^\d{10,14}$/', $number);
    }
}

?>