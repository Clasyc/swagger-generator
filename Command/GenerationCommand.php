<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('swagger:generate')
            ->setDescription('Generates controllers, actions and routes based on swagger definition paths.')
            ->setHelp('Before using this command define your bundles in config file. (swagger_generator: bundles: -YourBundle).')
            ->addArgument(
                'routesType',
                InputArgument::OPTIONAL,
                "Route types: 'annotation' or 'yml'.",
                "yml"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generator = $this->getContainer()->get('clasyc.generator');
        $generator->generate();
        $output->writeln([
            'Swagger generator have finished.'
        ]);

    }
}