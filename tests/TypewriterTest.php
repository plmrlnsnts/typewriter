<?php

use PHPUnit\Framework\TestCase;
use Plmrlnsnts\Typewriter\Entrypoint;
use Plmrlnsnts\Typewriter\Filesystem;
use Plmrlnsnts\Typewriter\InvalidField;
use Plmrlnsnts\Typewriter\LocalFilesystem;
use Plmrlnsnts\Typewriter\SharedDirectory;
use Plmrlnsnts\Typewriter\Source;
use Plmrlnsnts\Typewriter\Typewriter;
use Spatie\Snapshots\MatchesSnapshots;

class TypewriterTest extends TestCase
{
    use MatchesSnapshots;

    protected Typewriter $typewriter;

    protected Entrypoint $entrypoint;

    protected function setUp(): void
    {
        Filesystem::$instance = null;

        $this->typewriter = new Typewriter(
            source: __DIR__.'/schema.json',
            enums: new SharedDirectory(__DIR__.'/generated/Enums', 'Plmrlnsnts\\TypewriterApp\\Enums'),
            inputs: new SharedDirectory(__DIR__.'/generated/Inputs', 'Plmrlnsnts\\TypewriterApp\\Inputs'),
        );

        $this->entrypoint = new Entrypoint(
            input: __DIR__.'/documents',
            output: __DIR__.'/generated/Data',
            namespace: 'Plmrlnsnts\\TypewriterApp\\Data',
        );

        $this->typewriter->addEntrypoint($this->entrypoint);
    }

    protected function getSnapshotDirectory(): string
    {
        return __DIR__.'/snapshots';
    }

    public function test_it_can_generate_files(): void
    {
        $this->typewriter->generate();

        $files = (new LocalFilesystem)->scan(__DIR__.'/generated', pattern: '/.php$/i');

        foreach ($files as $file) {
            $this->assertMatchesFileHashSnapshot($file->getRealPath());
        }
    }

    public function test_it_uses_the_source_name_if_operation_name_is_empty(): void
    {
        $filesystem = Filesystem::fake(new Source(
            entrypoint: $this->entrypoint,
            name: 'FindProduct',
            content: 'query ($id: ID!) { product(id: $id) { id handle title } }'
        ));

        $this->typewriter->generate();

        $filesystem->assertHasOutput('FindProductResult');
        $filesystem->assertHasOutput('FindProductVariables');
    }

    public function test_it_rejects_an_invalid_field(): void
    {
        Filesystem::fake(new Source(
            entrypoint: $this->entrypoint,
            name: 'FindProduct',
            content: 'query ($id: ID!) { product(id: $id) { invalid } }'
        ));

        $this->expectException(InvalidField::class);

        $this->typewriter->generate();
    }
}
