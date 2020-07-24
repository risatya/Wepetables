# Wepetables
Wepetables will help you to use jQuery Datatables in server side with CodeIgniter4.

This is a wrapper class/library inspired and based on Ignited Datatables found at https://github.com/IgnitedDatatables/Ignited-Datatables for CodeIgniter 3.x.

## **Features**
1. Easy to use. Generates json using only a few lines of code.
2. Support for table joins (left, right, outer, inner, left outer, right outer).
3. Able to define custom columns, and filters.
4. Editable custom variables with callback function support.

## **Requirements**
* jQuery 1.5+
* DataTables 1.10+
* CodeIgniter 4.x "Reactor"

## **Installation**
To install the library please type this on your console
```
composer require wepe/wepetables
```
if you're using `--no-dev` package on your codeigniter, use this command
```
composer require wepe/wepetables --update-no-dev
```

## **Use Library**
Declare the following code in the controller that will use Wepetables.
```
use wepe\Wepetables;
```

## **Quick Start**
**HTML**
```
<table id="myDataTable">
    <thead>
        <tr>
            <th>id</th>
            <th>title</th>
            <th>date</th>
        </tr>
    </thead>
</table>
```
**JavaScript**
```
$(document).ready(function(){
    $('#myDataTable').DataTable({
	"processing": true,
	"serverSide": true,
	"ajax": {
	    "url": '<?=base_url('home/getdata');?>',
	    "type": "POST"
	}
    });
});
```
**Controller**
```
public function getdata(){
    $mytable = new Wepetables();
    $mytable->select('id, title, date');
    $mytable->from('mytable');
    $generated = $mytable->generate();
    return $this->response->setJSON($generated);
}
```

## **Usage & Example**
[Function Reference](https://github.com/we-pe/Wepetables/wiki/Function-Reference)

## **License**
MIT License.
