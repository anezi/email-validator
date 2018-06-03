<?php declare(strict_types = 1);

namespace Anezi\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email as BaseEmail;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Hassan Amouhzi <hassan@amouhzi.com>
 * @license Proprietary See License file.
 */
class Email extends BaseEmail
{
    public const THROWAWAY_CHECK_FAILED_ERROR = 4;

    /**
     * @var bool
     */
    public $checkThrowaway = false;

    public function __construct(array $options = null)
    {
        parent::__construct($options);

        self::$errorNames[self::THROWAWAY_CHECK_FAILED_ERROR] = 'THROWAWAY_CHECK_FAILED_ERROR';
    }
}
