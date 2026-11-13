<?php
/**
 * Base Model
 * AI Online Exam & Proctoring System
 */

abstract class Model
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
