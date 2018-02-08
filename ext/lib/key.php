<?php

/**
 * Crypt Key Generator Interface
 *
 * Author Jerry Shaw <jerry-shaw@live.com>
 * Author 秋水之冰 <27206617@qq.com>
 *
 * Copyright 2017 Jerry Shaw
 * Copyright 2018 秋水之冰
 *
 * This file is part of NervSys.
 *
 * NervSys is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NervSys is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NervSys. If not, see <http://www.gnu.org/licenses/>.
 */

namespace ext\lib;

interface key
{
    /**
     * Create Crypt Key
     *
     * @return string
     */
    public static function create(): string;

    /**
     * Extract Keys from Crypt Key
     *
     * @param string $key
     *
     * @return array
     */
    public static function extract(string $key): array;

    /**
     * Create obscured key from Crypt Key
     *
     * @param string $key
     *
     * @return string
     */
    public static function obscure(string $key): string;

    /**
     * Rebuild Crypt Key from obscured Key
     *
     * @param string $key
     *
     * @return string
     */
    public static function rebuild(string $key): string;
}