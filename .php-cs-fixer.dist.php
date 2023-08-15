<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('.git/')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setIndent("\t")
    ->setLineEnding("\n")
    ->setRules([
	'@PSR12' => true,
	'align_multiline_comment' => true,
	'array_syntax' => ['syntax' => 'short'],
	'binary_operator_spaces' => true,
	'blank_line_after_namespace' => true,
	'blank_line_after_opening_tag' => true,
	'cast_spaces' => ['space' => 'single'],
	'clean_namespace' => true,
	'combine_nested_dirname' => true,
	'concat_space' => ['spacing' => 'one'],
	'dir_constant' => true,
	'type_declaration_spaces' => true,
	'list_syntax' => ['syntax' => 'short'],
	'method_chaining_indentation' => true,
	'modernize_types_casting' => true,
	'no_blank_lines_after_phpdoc' => true,
	'no_null_property_initialization' => true,
	'no_whitespace_before_comma_in_array' => true,
	'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
	'phpdoc_indent' => true,
	'phpdoc_no_package' => true,
	'phpdoc_order' => true,
	'phpdoc_scalar' => ['types' => ['boolean', 'callback', 'integer', 'str']],
	'phpdoc_types' => true,
	'phpdoc_types_order' => true,
	'ternary_operator_spaces' => true,
	'ternary_to_null_coalescing' => true,
	'trim_array_spaces' => true,
	'visibility_required' => true,
	'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder);

return $config;
