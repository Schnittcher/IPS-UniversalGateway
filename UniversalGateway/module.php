<?php

declare(strict_types=1);
include 'VariableSynchronizer.php';

class UniversalGateway extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyString('Variables', '[]');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $configuration = $this->ReadPropertyString('Variables');
        if ($configuration != '') {
            $configuration = json_decode($configuration);
            foreach ($configuration as $key => $mapping) {
                $this->SendDebug(__FUNCTION__, 'RegisterMessage Variable 1: ' . $mapping->VariableID1, 0);
                $this->RegisterMessage($mapping->VariableID1, VM_UPDATE);

                // Only register Variable 2 if OneWay-Sync is disabled
                if (!isset($mapping->OneWay) || $mapping->OneWay == false) {
                    $this->SendDebug(__FUNCTION__, 'RegisterMessage Variable 2: ' . $mapping->VariableID2, 0);
                    $this->RegisterMessage($mapping->VariableID2, VM_UPDATE);
                }
            }
        }
    }

    public function MessageSink($timeStamp, $senderID, $message, $data)
    {
        $this->SendDebug(__FUNCTION__, $senderID . ' Message: ' . $message, 0);
        switch ($message) {
            case VM_UPDATE:
                foreach ($this->GetMappingsForId($senderID) as $mapping) {
                    $conversion = $this->GetConversion($mapping, $senderID);
                    $targetVariableID = $this->GetAssociatedTargetID($mapping, $senderID);
                    $updateTargetWithoutValueChange = $mapping->OneWay && $mapping->TriggerOnlyOnValueChange == false;
                    $sendDebug = function ($source, $message)
                    {
                        $this->SendDebug($source, $message, 0);
                    };

                    $synchronizer = new VariableSynchronizer($senderID, $targetVariableID, $conversion, $updateTargetWithoutValueChange, $sendDebug);
                    $synchronizer->UpdateTarget();
                }
                break;
        }
    }

    private function GetMappingsForId($variableSourceID)
    {
        $configJSON = $this->ReadPropertyString('Variables');
        $mappings = json_decode($configJSON);
        foreach ($mappings as $mapping) {
            if (isset($mapping->OneWay) && $mapping->OneWay) {
                if ($mapping->VariableID1 == $variableSourceID) {
                    yield $mapping;
                }
            } else {
                if ($mapping->VariableID1 == $variableSourceID || $mapping->VariableID2 == $variableSourceID) {
                    yield $mapping;
                }
            }
        }
    }

    private function GetAssociatedTargetID($mapping, $variableSourceID)
    {
        if ($mapping->VariableID1 == $variableSourceID) {
            return $mapping->VariableID2;
        } elseif ($mapping->VariableID2 == $variableSourceID) {
            return $mapping->VariableID1;
        }
        return false;
    }

    private function GetConversion($mapping, $variableSourceID)
    {
        if ($mapping->VariableID1 == $variableSourceID && isset($mapping->ConversionVar1ToVar2)) {
            return $mapping->ConversionVar1ToVar2;
        }
        return false;
    }
}
