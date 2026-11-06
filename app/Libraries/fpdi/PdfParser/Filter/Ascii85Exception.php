<?php

/**
 * This file is part of FPDI
 *
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Exception for Ascii85 filter class
 */
class Ascii85Exception extends FilterException
{
    /**
     * @var int
     */
    const ILLEGAL_CHAR_FOUND = 0x0301;

    /**
     * @var int
     */
    const ILLEGAL_LENGTH = 0x0302;
}
