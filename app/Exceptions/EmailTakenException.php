<?php

namespace App\Exceptions;

use Exception;

/** Signup intake: the email already belongs to an existing account. */
class EmailTakenException extends Exception {}
