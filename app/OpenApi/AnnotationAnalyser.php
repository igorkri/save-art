<?php

namespace App\OpenApi;

use OpenApi\Analysers\AnalyserInterface;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Analysis;
use OpenApi\Context;
use OpenApi\Generator;

class AnnotationAnalyser implements AnalyserInterface
{
    private ?Generator $generator = null;

    public function setGenerator(Generator $generator): static
    {
        $this->generator = $generator;

        return $this;
    }

    public function fromFile(string $filename, Context $context): Analysis
    {
        return $this->analyser()->fromFile($filename, $context);
    }

    public function fromFqdn(string $fqdn, Analysis $analysis): Analysis
    {
        return $this->analyser()->fromFqdn($fqdn, $analysis);
    }

    private function analyser(): ReflectionAnalyser
    {
        $analyser = new ReflectionAnalyser([
            new AttributeAnnotationFactory,
            new DocBlockAnnotationFactory,
        ]);

        if ($this->generator !== null) {
            $analyser->setGenerator($this->generator);
        }

        return $analyser;
    }

    /**
     * Recreate the analyser when Laravel loads its exported config cache.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self;
    }
}
