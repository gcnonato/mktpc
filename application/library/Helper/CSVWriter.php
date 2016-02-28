<?php
/** 
 * Simple class to properly output CSV data to clients. PHP 5 has a built 
 * in method to do the same for writing to files (fputcsv()), but many times 
 * going right to the client is beneficial. 
 * 
 * @author Jon Gales 
 * 
 * $data = array(array("one","two","three"), array(4,5,6));  
 * $csv = new CSV_Writer($data);  
 * $csv->headers('test');  
 * $csv->output();
 */  
  
class Helper_CSVWriter {  
  
    public $data = array();  
    public $deliminator;  
  
    /** 
     * Loads data and optionally a deliminator. Data is assumed to be an array 
     * of associative arrays. 
     * 
     * @param array $data 
     * @param string $deliminator 
     */  
    function __construct($data, $deliminator = ",")  
    {  
        if (!is_array($data))  
        {  
            throw new Exception('Helper_CSVWriter only accepts data as arrays');  
        }  
  
        $this->data = $data;  
        $this->deliminator = $deliminator;  
    }  
  
    private function wrap_with_quotes($data)  
    {  
        $data = preg_replace('/(.+)/', '"$1"', $data);  
        return sprintf('%s', $data);  
    }  
  
    /** 
     * Echos the escaped CSV file with chosen delimeter 
     * 
     * @return void 
     */  
    public function output()  
    {  
        foreach ($this->data as $row)  
        {  
            $quoted_data = array_map(array('Helper_CSVWriter', 'wrap_with_quotes'), $row); 
            echo sprintf("%s\n", implode($this->deliminator, $quoted_data));  
        }  
    }  
  
    /** 
     * Sets proper Content-Type header and attachment for the CSV outpu 
     * 
     * @param string $name 
     * @return void 
     */  
    public function headers($name)  
    {  
      //  header('Content-Type: application/csv');  
      //  header("Content-disposition: attachment; filename={$name}.csv");  
      
		header('Content-Type: application/text/x-csv; charset=utf-8; encoding=utf-8');
		header('Content-Disposition: attachment; filename="'. $name .'.csv"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header("Content-Transfer-Encoding: binary");
		header('Expires: 0');
    }  
}  
?>