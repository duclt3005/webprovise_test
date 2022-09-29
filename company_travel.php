<?php
abstract class CompanyTravelAbstract
{
    protected $dataApi;

    public function getDataFromApi($url)
    {
        $content =     file_get_contents($url);
        $result  = json_decode($content);
        $this->dataApi = [];
        foreach ($result as $value)
        {
            $value = (array)$value;
            if (is_a($this, "Company"))
            {
                $value["cost"] = 0;
            }
            $this->dataApi[] = $value;
        }

        return $this->dataApi;
    }
}

class Company extends CompanyTravelAbstract
{
}

class Travel extends CompanyTravelAbstract
{
}

class TestScript
{
    private $companies;
    private $travels;

    public function execute()
    {
        $this->companies = (new Company())->getDataFromApi("https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies");
        $this->travels = (new Travel())->getDataFromApi("https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels");

        $this->__mergeCost();
        $companyFormat = $this->__process();
        $this->__storeInFile($companyFormat);
    }

    private function __mergeCost()
    {
        foreach ($this->travels as $travel)
        {
            $key = array_search($travel["companyId"], array_column($this->companies, "id"));
            if ($key !== false)
            {
                $this->companies[$key]["cost"] += $travel["price"];
            }
        }
    }

    private function __process()
    {
        $newArray = [];
        foreach ($this->companies as $a)
        {
            $newArray[$a['parentId']][] = $a;
        }
        $tree = $this->__createTree($newArray, array($this->companies[0]));
        $this->__sum($tree[0]);
        return $tree;
    }

    private function __createTree(&$list, $parent)
    {
        $tree = [];
        foreach ($parent as $k => $l)
        {
            if (isset($list[$l['id']]))
            {
                $l['children'] = $this->__createTree($list, $list[$l['id']]);
            }
            $tree[] = $l;
        }
        return $tree;
    }

    private function __sum(&$tree)
    {
        if (isset($tree["children"]))
        {
            for ($i = 0; $i < count($tree["children"]); $i++)
            {
                $tree["cost"] += $this->__sum($tree["children"][$i]);
            }
        }

        return $tree["cost"];
    }

    private function __storeInFile($data)
    {
        file_put_contents("result.txt", json_encode($data));
    }
}
(new TestScript())->execute();
