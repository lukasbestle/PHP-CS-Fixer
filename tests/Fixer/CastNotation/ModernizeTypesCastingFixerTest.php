<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\CastNotation;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\AbstractFunctionReferenceFixer
 * @covers \PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer
 */
final class ModernizeTypesCastingFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        $multiLinePatternToFix = <<<'FIX'
<?php $x =
intval

(
    mt_rand
    (
        0, 100
    )

)

;
FIX;
        $multiLinePatternFixed = <<<'FIXED'
<?php $x =
(int) (
    mt_rand
    (
        0, 100
    )

)

;
FIXED;

        $overriddenFunction = <<<'OVERRIDDEN'
<?php

class overridesIntval
{
    public function intval($x)
    {
        return \intval($x);
    }

    public function usesInval()
    {
        // that's why it risky
        return intval(mt_rand(0, 100));
    }
}
OVERRIDDEN;

        $overriddenFunctionFixed = <<<'OVERRIDDEN'
<?php

class overridesIntval
{
    public function intval($x)
    {
        return (int) $x;
    }

    public function usesInval()
    {
        // that's why it risky
        return (int) (mt_rand(0, 100));
    }
}
OVERRIDDEN;

        return [
            ['<?php $x = "intval";'],

            ['<?php $x = ClassA::intval(mt_rand(0, 100));'],
            ['<?php $x = ScopeA\\intval(mt_rand(0, 100));'],
            ['<?php $x = namespace\\intval(mt_rand(0, 100));'],
            ['<?php $x = $object->intval(mt_rand(0, 100));'],

            ['<?php $x = new \\intval(mt_rand(0, 100));'],
            ['<?php $x = new intval(mt_rand(0, 100));'],
            ['<?php $x = new ScopeB\\intval(mt_rand(0, 100));'],

            ['<?php intvalSmth(mt_rand(0, 100));'],
            ['<?php smth_intval(mt_rand(0, 100));'],

            ['<?php "SELECT ... intval(mt_rand(0, 100)) ...";'],
            ['<?php "test" . "intval" . "in concatenation";'],

            ['<?php $x = intval($x, 16);'],
            ['<?php $x = intval($x, $options["base"]);'],
            ['<?php $x = intval($x, $options->get("base", 16));'],

            ['<?php $x = (int) $x;', '<?php $x = intval($x);'],
            ['<?php $x = (float) $x;', '<?php $x = floatval($x);'],
            ['<?php $x = (float) $x;', '<?php $x = doubleval($x);'],
            ['<?php $x = (string) $x;', '<?php $x = strval($x);'],
            ['<?php $x = (bool) $x;', '<?php $x = boolval   (  $x  );'],
            ['<?php $x = (int) (mt_rand(0, 100));', '<?php $x = intval(mt_rand(0, 100));'],
            ['<?php $x = (int) (mt_rand(0, 100));', '<?php $x = \\intval(mt_rand(0, 100));'],
            ['<?php $x = (int) (mt_rand(0, 100)).".dist";', '<?php $x = intval(mt_rand(0, 100)).".dist";'],
            ['<?php $x = (int) (mt_rand(0, 100)).".dist";', '<?php $x = \\intval(mt_rand(0, 100)).".dist";'],

            [$multiLinePatternFixed, $multiLinePatternToFix],
            [$overriddenFunctionFixed, $overriddenFunction],

            [
                '<?php $a = (string) ($b . $c);',
                '<?php $a = strval($b . $c);',
            ],
            [
                '<?php $x = /**/(int) /**/ /** x*/(/**//** */mt_rand(0, 100)/***/)/*xx*/;',
                '<?php $x = /**/intval/**/ /** x*/(/**//** */mt_rand(0, 100)/***/)/*xx*/;',
            ],
            [
                '<?php $x = (string) ((int) ((int) $x + (float) $x));',
                '<?php $x = strval(intval(intval($x) + floatval($x)));',
            ],
            [
                '<?php intval();intval(1,2,3);',
            ],
            [
                '<?php
                interface Test
                {
                    public function floatval($a);
                    public function &doubleval($a);
                }',
            ],
            [
                '<?php $foo = ((int) $x)**2;',
                '<?php $foo = intval($x)**2;',
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $input
     *
     * @requires PHP 7.0
     * @dataProvider provideFix70Cases
     */
    public function testFix70($expected, $input)
    {
        $this->doTest($expected, $input);
    }

    public function provideFix70Cases()
    {
        return [
            [
                '<?php $foo = ((string) $x)[0];',
                '<?php $foo = strval($x)[0];',
            ],
            [
                '<?php $foo = ((string) ($x + $y))[0];',
                '<?php $foo = strval($x + $y)[0];',
            ],
            [
                '<?php $foo = ((string) ($x + $y)){0};',
                '<?php $foo = strval($x + $y){0};',
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $input
     *
     * @requires PHP 7.3
     * @dataProvider provideFix73Cases
     */
    public function testFix73($expected, $input)
    {
        $this->doTest($expected, $input);
    }

    public function provideFix73Cases()
    {
        return [
            [
                '<?php $a = (int) $b;',
                '<?php $a = intval($b, );',
            ],
            [
                '<?php $a = (int) $b;',
                '<?php $a = intval($b , );',
            ],
            [
                '<?php $a = (string) ($b . $c);',
                '<?php $a = strval($b . $c, );',
            ],
        ];
    }

    /**
     * @requires PHP <8.0
     */
    public function testFixPrePHP80()
    {
        $this->doTest(
            '<?php $a = #
#
#
(int) #
 (
#
 $b#
 )#
 ;#',
            '<?php $a = #
#
\
#
intval#
 (
#
 $b#
 )#
 ;#'
        );
    }
}
