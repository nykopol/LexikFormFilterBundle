<?php

namespace Lexik\Bundle\FormFilterBundle\Tests\Filter\Doctrine;

use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemEmbeddedOptionsFilterType;

/**
 * Filter query builder tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ORMQueryBuilderUpdaterTest extends DoctrineQueryBuilderUpdater
{
    public function testBuildQuery()
    {
        parent::createBuildQueryTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\'',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\' AND i.position > :p_i_position',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\' AND i.position > :p_i_position AND i.enabled = 1',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\' AND i.position > :p_i_position AND i.enabled = 1',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'%blabla\' AND i.position <= :p_i_position AND i.createdAt = \'2013-09-27\'',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'%blabla\' AND i.position <= :p_i_position AND i.createdAt = \'2013-09-27 13:21:00\'',
        ));
    }

    public function testApplyFilterOption()
    {
        parent::createApplyFilterOptionTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name <> \'blabla\' AND i.position <> 2',
        ));
    }

    public function testNumberRange()
    {
        parent::createNumberRangeTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.position > :p_i_position_left AND i.position < :p_i_position_right',
        ));
    }

    public function testNumberRangeWithSelector()
    {
        parent::createNumberRangeCompoundTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.position_selector > :p_i_position_selector_left AND i.position_selector <= :p_i_position_selector_right',
        ));
    }

    public function testNumberRangeDefaultValues()
    {
        parent::createNumberRangeDefaultValuesTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.default_position >= :p_i_default_position_left AND i.default_position <= :p_i_default_position_right',
        ));
    }

    public function testDateRange()
    {
        parent::createDateRangeTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.createdAt <= \'2012-05-22\' AND i.createdAt >= \'2012-05-12\'',
        ));
    }

    public function testDateTimeRange()
    {
        parent::createDateTimeRangeTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.updatedAt <= \'2012-06-10 22:12:00\' AND i.updatedAt >= \'2012-05-12 14:55:00\'',
        ));
    }

    public function testFilterStandardType()
    {
        parent::createFilterStandardTypeTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'%hey dude%\' AND i.position = 99',
        ));
    }

    public function testEmbedFormFilter()
    {
        // doctrine query builder without any joins
        $form = $this->formFactory->create(new ItemEmbeddedOptionsFilterType());
        $filterQueryBuilder = $this->initQueryBuilder();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->bind(array('name' => 'dude', 'options' => array(array('label' => 'color', 'rank' => 3))));

        $expectedDql = 'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';
        $expectedDql .= ' LEFT JOIN i.options opt WHERE i.name LIKE \'dude\' AND opt.label LIKE \'color\' AND opt.rank = :p_opt_rank';
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals(array('p_opt_rank' => 3), $this->getQueryBuilderParameters($doctrineQueryBuilder));


        // doctrine query builder with joins
        $form = $this->formFactory->create(new ItemEmbeddedOptionsFilterType());
        $filterQueryBuilder = $this->initQueryBuilder();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $doctrineQueryBuilder->leftJoin('i.options', 'o');
        $form->bind(array('name' => 'dude', 'options' => array(array('label' => 'size', 'rank' => 5))));

        $expectedDql = 'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';
        $expectedDql .= ' LEFT JOIN i.options o WHERE i.name LIKE \'dude\' AND o.label LIKE \'size\' AND o.rank = :p_o_rank';

        $filterQueryBuilder->setParts(array('i.options' => 'o'));
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals(array('p_o_rank' => 5), $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    protected function createDoctrineQueryBuilder()
    {
        return $this->em
                     ->getRepository('Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item')
                     ->createQueryBuilder('i');
    }
}
