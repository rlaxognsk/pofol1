<?php
namespace Pofol\View\PFTEngine;

use Exception;

class Compiler
{
    protected $contents;
    protected $file;

    public function compile($file)
    {
        $this->file = realpath($file);
        $this->setFileContents();

        $this->compileExpression();
        $this->compileEscapeExpression();
        $this->compileOr();
        $this->compileIf();
        $this->compileFor();
        $this->compileForeach();
        $this->compileForelse();
        $this->compileExtends();
        $this->compileImport();

        return trim($this->contents);
    }

    protected function setFileContents()
    {
        if (!ob_start()) {
            throw new Exception('ob_start() failed.');
        }

        readfile($this->file);

        $this->contents = ob_get_clean();
    }

    protected function compileExpression()
    {
        $patterns = [
            '/\{\{(.*)\}\}/',
        ];

        $replacements = [
            '<?php echo e(${1}); ?>',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);
    }

    protected function compileEscapeExpression()
    {
        $patterns = [
            '/\{\!(.*)\!\}/',
        ];

        $replacements = [
            '<?php echo ${1}; ?>',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);
    }

    protected function compileOr()
    {
        $patterns = [
            '/(\$\w*)\s*or\s*(\$?\w*)/',
        ];
        $replacements = [
            'isset(${1}) ? ${1} : ${2}',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);
    }

    protected function compileIf()
    {
        $patterns = [
            '/@\s*if\s*\((.*)\)/',
            '/@\s*elseif\s*\((.*)\)/',
            '/@\s*else/',
            '/@\s*endif/',
        ];

        $replacements = [
            '<?php if (${1}): ?>',
            '<?php elseif (${1}): ?>',
            '<?php else: ?>',
            '<?php endif; ?>',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);
    }

    protected function compileFor()
    {
        $patterns = [
            '/@\s*for\s*\((.*)\)/',
            '/@\s*endfor\b/',
        ];

        $replacements = [
            '<?php for (${1}): ?>',
            '<?php endfor; ?>',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);
    }

    protected function compileForeach()
    {
        $patterns = [
            '/@\s*foreach\s*\((.*)\)/',
            '/@\s*endforeach/',
        ];

        $replacements = [
            '<?php foreach (${1}): ?>',
            '<?php endforeach; ?>',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);
    }

    protected function compileForelse()
    {
        $patterns = [
            '/@\s*forelse\s*\((\s*(\$\w*)\s*.*)\)/',
            '/@\s*empty/',
            '/@\s*endforelse/',
        ];

        $replacements = [
            '<?php if (!empty(${2})):' . 'foreach (${1}): ?>',
            '<?php endforeach; else: ?>',
            '<?php endif; ?>',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);
    }

    protected function compileExtends()
    {
        $patterns = [
            '/@\s*section\s*\(\'(\w*)\'\)/',
            '/@\s*endsection/',
            '/@\s*yield\s*\(\'(\w*)\'\)/',
        ];

        $replacements = [
            '<?php __viewExtends()->addSection(\'${1}\'); ?>',
            '<?php __viewExtends()->endSection(); ?>',
            '<?php __viewExtends()->yieldSection(\'${1}\'); ?>',
        ];

        $this->contents = preg_replace($patterns, $replacements, $this->contents);

        $pattern = '/@\s*extends\s*\(\'(\w*)\'\)/';
        $matches = [];

        if (preg_match($pattern, $this->contents, $matches) === 1) {
            $this->contents = preg_replace($pattern, '', $this->contents);
            $this->contents .= PHP_EOL . "<?php __viewExtends()->import('{$matches[1]}', get_defined_vars()); ?>";
        }
    }

    protected function compileImport()
    {
        $pattern = '/@\s*import\s*\(\'(\w*)\'\)/';

        $replacement = '<?php __viewExtends()->import(\'${1}\', get_defined_vars()); ?>';

        $this->contents = preg_replace($pattern, $replacement, $this->contents);
    }
}
