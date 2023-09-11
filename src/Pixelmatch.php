<?php

namespace Spatie\Pixelmatch;

use InvalidArgumentException;
use Spatie\Pixelmatch\Actions\ExecuteNodeAction;
use Spatie\Pixelmatch\Concerns\HasOptions;
use Spatie\Pixelmatch\Enums\Output;

class Pixelmatch
{
    protected string $workingDirectory;

    /*
     * If true, we'll ignore anti-aliased pixels
     */
    protected bool $includeAa;

    /*
     * Smaller values make the comparison more sensitive
     */
    protected float $threshold;

    protected function __construct(
        public string $pathToImage1,
        public string $pathToImage2,
        public $executeNodeAction = new ExecuteNodeAction(),
    ) {
        $this->workingDirectory = (string) realpath(dirname(__DIR__));
    }

    public static function new(string $pathToImage1, string $pathToImage2): self
    {
        return new static($pathToImage1, $pathToImage2);
    }

    public function includeAa(bool $includeAa = true): self
    {
        $this->includeAa = $includeAa;

        return $this;
    }

    public function threshold(float $threshold): self
    {
        if ($threshold > 1 || $threshold < 0) {
            throw new InvalidArgumentException('Threshold should be between 0 and 1');
        }

        $this->threshold = $threshold;

        return $this;
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        $options = [];

        if (isset($this->includeAa)) {
            $options['includeAA'] = $this->includeAa;
        }

        if (isset($this->threshold)) {
            $options['threshold'] = $this->threshold;
        }

        return $options;
    }

    public function matchingPercentage(): int
    {
        return $this->run(Output::Percentage);
    }

    public function mismatchingPercentage(): int
    {
        return 100 - $this->run(Output::Percentage);
    }

    public function mismatchingPixels(): int
    {
        return $this->run(Output::Pixels);
    }

    protected function run(Output $output): int
    {
        $arguments = Arguments::new($output, $this);

        $result = $this->executeNodeAction->execute(
            workingDir: $this->workingDirectory,
            arguments: $arguments->toArray(),
        );

        return (int) json_decode($result, true);
    }
}
