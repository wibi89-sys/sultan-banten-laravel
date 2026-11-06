<?php

/**
 * This file is part of FPDI
 *
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Exception for flate filter class
 */
class FlateException extends FilterException
{
    /**
     * @var int
     */
    const NO_ZLIB = 0x0401;

    /**
     * @var int
     */
    const DECOMPRESS_ERROR = 0x0402;
}
