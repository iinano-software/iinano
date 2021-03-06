<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Tests\Functional\Cli {

    use Symfony\Component\Console\Tester\CommandTester;
    use Symfony\Bundle\FrameworkBundle\Console\Application;
    use Trismegiste\SocialBundle\Cli\MapReduceJob;
    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

    /**
     * MapReduceJobTest tests the MapReduceJob CLI
     */
    class MapReduceJobTest extends WebTestCase
    {

        public function testExecute()
        {
            $kernel = static::createKernel();
            $kernel->boot();

            $application = new Application($kernel);
            $application->add(new MapReduceJob());
            $command = $application->find('social:mr');
            $commandTester = new CommandTester($command);

            $commandTester->execute(['command' => $command->getName(), 'classname' => 'DummyMru']);
            $this->assertRegExp('#dummymru#', $commandTester->getDisplay());
            $this->assertRegExp('#finished after#', $commandTester->getDisplay());
        }

    }

}

// fixtures

namespace Trismegiste\SocialBundle\Repository\MapReduce {

    // a fake MruService
    class DummyMru extends MruService
    {

        protected function mapReduce()
        {

        }

        protected function update()
        {

        }

    }

}