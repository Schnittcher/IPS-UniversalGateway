<?php

declare(strict_types=1);
class VariableSynchronizer
{
    private $variableSourceID;
    private $variableTargetID;
    private $valueConversion;
    private $updateTargetWithoutValueChange;
    private $sendDebugFunction;
    public function __construct($variableSourceID, $variableTargetID, $valueConversion, $updateTargetWithoutValueChange, $sendDebugFunction)
    {
        $this->variableSourceID = $variableSourceID;
        $this->variableTargetID = $variableTargetID;
        $this->valueConversion = $valueConversion;
        $this->updateTargetWithoutValueChange = $updateTargetWithoutValueChange;
        $this->sendDebugFunction = $sendDebugFunction;
    }

    public function UpdateTarget()
    {
        if ($this->variableTargetID == false || $this->variableSourceID == false) {
            return;
        }

        $this->UpdateTargetVariable();
    }

    private function UpdateTargetVariable()
    {
        $sourceValue = GetValue($this->variableSourceID);
        $newValue = $this->GetConvertedValue($this->valueConversion, $sourceValue);

        if (is_null($newValue)) {
            ($this->sendDebugFunction)(__FUNCTION__, 'Skipped update of variable ' . $this->variableTargetID . '. Conversion was null.');
            return;
        }

        if ($this->updateTargetWithoutValueChange == false) {
            $targetCurrentValue = GetValue($this->variableTargetID);
            if ($targetCurrentValue == $newValue) {
                ($this->sendDebugFunction)(__FUNCTION__, 'Skipped update of variable ' . $this->variableTargetID . '. Value is equal to new value.');
                return;
            }
        }

        RequestAction($this->variableTargetID, $newValue);
        ($this->sendDebugFunction)(__FUNCTION__, 'Updated variable ' . $this->variableTargetID . ' from variable ' . $this->variableSourceID . '. New Value: ' . $newValue);
    }

    private function GetConvertedValue($conversion, $OriginalValue)
    {
        if ($conversion == false) {
            return $OriginalValue;
        }
        $ConversionFunction = function ($conversion, $Value)
        {
            return eval('return ' . $conversion . ';');
        };
        return $ConversionFunction($conversion, $OriginalValue);
    }
}
