<?php

namespace Marquine\Chronos\Diff\Renderers;

use cogpowered\FineDiff\Render\Renderer;

class Insert extends Renderer
{
    /**
     * Render an insertion diff.
     *
     * @param  string  $opcode
     * @param  string  $subject
     * @param  int  $offset
     * @param  int  $length
     * @return string
     */
    public function callback($opcode, $subject, $offset, $length)
    {
        if ($opcode === 'd') {
            return;
        }

        if ($opcode === 'c') {
            return  htmlentities(substr($subject, $offset, $length));
        }

        return '<ins>'.htmlentities(substr($subject, $offset, $length)).'</ins>';
    }
}
