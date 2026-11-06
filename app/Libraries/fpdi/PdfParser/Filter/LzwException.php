<?php

/**
 * This file is part of FPDI
 *
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Exception for LZW filter class
 */
class LzwException extends FilterException
{
    /**
     * @var int
     */
    const LZW_FLAVOUR_NOT_SUPPORTED = 0x0501;
}
