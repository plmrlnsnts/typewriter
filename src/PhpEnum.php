<?php

namespace Plmrlnsnts\Typewriter;

use Nette\PhpGenerator\PhpNamespace;

class PhpEnum extends PhpFile
{
    /** @param list<PhpEnumCase> $cases */
    public function __construct(
        public string $name,
        public string $namespace,
        public string $path,
        public array $cases
    ) {
        //
    }

    public function render(): string
    {
        $namespace = new PhpNamespace($this->namespace);
        $enum = $namespace->addEnum($this->name);

        foreach ($this->cases as $option) {
            $case = $enum->addCase($option->name, $option->value);

            if ($option->description) {
                $case->addComment($option->description);
            }

            if ($option->deprecated) {
                $case->addAttribute('Deprecated', array_filter(['message' => $option->deprecationReason]));
                $namespace->addUse('Deprecated');
            }
        }

        return (string) '<?php'.PHP_EOL.PHP_EOL.$namespace;
    }
}
