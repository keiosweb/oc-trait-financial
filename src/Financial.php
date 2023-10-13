<?php namespace Keios\Financial;

use Keios\MoneyRight\Money;

/**
 * Trait Financial
 *
 * @package Keios\Financial
 */
trait Financial
{
    /**
     * @var array List of attribute names which should be transformed to Money value object
     *
     * protected $financial = [];
     */

    /**
     * @var array List of attributes registered as immutable money object fields
     */
    private $protectedFieldNames = [];

    /**
     * @var bool
     */
    protected static $financialTraitAlreadyBooted = false;

    /**
     * Boot the financial trait for a model.
     *
     * @throws \Exception
     * @return void
     */
    public static function bootFinancial()
    {
        if (static::$financialTraitAlreadyBooted) {
            return;
        }

        static::$financialTraitAlreadyBooted = true;

        if (!property_exists(get_called_class(), 'financial')) {
            throw new \Exception(
                sprintf('You must define a $financial property in %s to use the Financial trait.', get_called_class())
            );
        }

        /*
         * Transform data
         */
        static::extend(
            function ($model) {

                $financialAttributes = $model->getFinancialConfiguration();

                $model->validateFinancialConfiguration($financialAttributes);

                $model->guardProtectedFields($financialAttributes);

                $financialObjectFields = array_keys($financialAttributes);

                $model->bindEvent(
                    'model.beforeSetAttribute',
                    function ($key, $value) use ($model, $financialAttributes, $financialObjectFields) {
                        /*
                         * if key is listed as protected field
                         * that constitute Financial data, quit
                         */
                        if (in_array($key, $model->getProtectedFieldNames())) {
                            throw new ProtectedFieldException('Cannot save to protected field');
                        }

                        if (in_array($key, $financialObjectFields)) {
                            if (!$value instanceof Money) {
                                throw new \InvalidArgumentException(
                                    'Field '.$key.' can be only set with Keios\MoneyRight\Money object.'
                                );
                            }
                        }
                    }
                );

                $model->bindEvent(
                    'model.afterFetch',
                    function () use ($model) {
                        $model->createFinancialAttributes();
                    }
                );

                $model->bindEvent(
                    'model.saveInternal',
                    function () use ($model, $financialAttributes, $financialObjectFields) {
                        $model->setFinancialFieldsWithMoneyData();
                        $model->purgeFinancialFields();
                    }
                );

                $model->bindEvent(
                    'model.afterSave',
                    function () use ($model) {
                        $model->createFinancialAttributes();
                    }
                );
            }
        );
    }

    /**
     * Getter for private $protectedFieldNames
     *
     * @return array
     */
    public function getProtectedFieldNames()
    {
        return $this->protectedFieldNames;
    }

    /**
     * Lists fields that shouldn't be accessible from outer context
     *
     * @param array $financialAttributes
     */
    public function guardProtectedFields(array $financialAttributes)
    {
        foreach ($financialAttributes as $attribute => $configuration) {
            $this->protectedFieldNames[] = $configuration['balance'];
            $this->protectedFieldNames[] = $configuration['currency'];
        }
    }

    /**
     * Build Money instances on configured fields and removes financial source fields from attributes
     *
     * @throws \Exception
     */
    public function createFinancialAttributes()
    {
        $financialAttributes = $this->getFinancialConfiguration();
        foreach ($financialAttributes as $attribute => $configuration) {
            $this->attributes[$attribute] = $this->getMoneyValueObject($attribute);
        }

    }


    /**
     * Checks that $financial field contains valid configuration
     *
     * @param array $financialAttributes
     *
     * @throws \Exception
     */
    public function validateFinancialConfiguration(array $financialAttributes)
    {
        foreach ($financialAttributes as $key => $value) {
            if (!is_array($value) || !array_key_exists('balance', $value) || !array_key_exists('currency', $value)) {
                throw new \Exception(
                    'Invalid financial configuration, has to be ["field" => ["balance"=>"", "currency"=>""]].'
                );
            }
        }
    }

    /**
     * Extracts data from Money object and sets it directly in related source attributes
     */
    public function setFinancialFieldsWithMoneyData()
    {
        $financialAttributes = $this->getFinancialConfiguration();
        foreach ($financialAttributes as $attribute => $configuration) {
            if (!isset($this->attributes[$configuration['balance']])) {
                $this->attributes[$configuration['balance']] = $this->attributes[$attribute]->getAmountString();
            }
            if (!isset($this->attributes[$configuration['currency']])) {
                $this->attributes[$configuration['currency']] = $this->attributes[$attribute]->getCurrency()
                                                                                             ->getIsoCode();
            }
        }
    }

    /**
     * Builds Money Value Object from data stored in database
     *
     * @param  string $key Attribute
     *
     * @return \Keios\MoneyRight\Money   Money Value Object
     */
    public function getMoneyValueObject($key)
    {
        $financialAttributes = $this->getFinancialConfiguration();
        $balance = array_get($this->attributes, $financialAttributes[$key]['balance']);
        $currencyIso = array_get($this->attributes, $financialAttributes[$key]['currency']);

        return Money::$currencyIso($balance);
    }

    /**
     * Returns configuration of financial attributes
     *
     * @throws \Exception
     * @return array
     */
    public function getFinancialConfiguration()
    {
        if (!is_array($this->financial)) {
            throw new \Exception('Financial configuration has to be defined as array.');
        }

        return $this->financial;
    }

    /**
     * Removes financial model attribute
     *
     * @throws \Exception
     */
    public function purgeFinancialFields()
    {
        $financialAttributes = $this->getFinancialConfiguration();
        foreach ($financialAttributes as $attribute => $configuration) {
            unset($this->attributes[$attribute]);
        }
    }

}
