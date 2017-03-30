<?php

namespace Marquine\Chronos\Diff\Renderers;

use cogpowered\FineDiff\Render\Renderer;

class Delete extends Renderer
{
    /**
     * Render an deletion diff.
     *
     * @param  string  $opcode
     * @param  string  $subject
     * @param  int  $offset
     * @param  int  $length
     * @return string
     */
    public function callback($opcode, $subject, $offset, $length)
    {
        if ($opcode === 'i') {
            return;
        }

        if ($opcode === 'c') {
            return  htmlentities(substr($subject, $offset, $length));
        }

        $deletion = substr($subject, $offset, $length);

        if (strcspn($deletion, " \n\r") === 0) {
            $deletion = str_replace(array("\n","\r"), array('\n','\r'), $deletion);
        }

        return '<del>'.htmlentities($deletion).'</del>';
    }
}
