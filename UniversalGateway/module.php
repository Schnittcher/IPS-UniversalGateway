<?php

declare(strict_types=1);

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

        $DevicesJSON = $this->ReadPropertyString('Variables');
        if ($DevicesJSON != '') {
            $Variables = json_decode($DevicesJSON);
            foreach ($Variables as $key=>$Variable) {
                $this->RegisterMessage($Variable->VariableID1, VM_UPDATE);
                $this->RegisterMessage($Variable->VariableID2, VM_UPDATE);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, 'SenderID '.$SenderID.' Message: '.$Message, 0);
        switch ($Message) {
            case VM_UPDATE:
                $AssociatedVariable = $this->getAssociatedVariable($SenderID);
                $this->SendDebug(__FUNCTION__, 'SenderID '.$SenderID.' AssociatedVariable: '.$AssociatedVariable, 0);
                if ($AssociatedVariable) {
                    RequestAction($AssociatedVariable,GetValue($SenderID));
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, $SenderID.' Message: '.$Message, 0);
                break;
        }
    }

    private function getAssociatedVariable($VariableID) {
        $VariablesJSON = $this->ReadPropertyString('Variables');
        $Variables = json_decode($VariablesJSON);
        foreach ($Variables as $Variable) {
            if ($Variable->VariableID1 == $VariableID) {
                return $Variable->VariableID2;
            } elseif ($Variable->VariableID2 == $VariableID) {
                return $Variable->VariableID1;
            } else {
                return false;
            }
        }
    }
}