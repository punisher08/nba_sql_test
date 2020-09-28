<?php
use Illuminate\Support;
use LSS\Array2Xml;
$database = 'nba2019';
require_once('vendor/autoload.php');
require_once('include/utils.php');

// retrieves & formats data from the database for export



class Exporter {
    public function __construct() {
        

    }
    static function myFunction($search)
    {
        $where = [];
        if ($search->has('playerId')) $where[] = "roster.id = '" . $search['playerId'] . "'";
        if ($search->has('player')) $where[] = "roster.name = '" . $search['player'] . "'";
        if ($search->has('team')) $where[] = "roster.team_code = '" . $search['team']. "'";
        if ($search->has('position')) $where[] = "roster.pos = '" . $search['position'] . "'";
        if ($search->has('country')) $where[] = "roster.nationality = '" . $search['country'] . "'";
        return implode(' AND ', $where);
        
    }
    function getPlayerStats($search) {

        $sqlWhere = $this->myFunction($search);
        $sql = "SELECT roster.name, player_totals.*FROM player_totals INNER JOIN roster ON (roster.id = player_totals.player_id) WHERE $sqlWhere";
    
        $data = query($sql) ?: [];
        return collect(query($sql))
            ->map(function($item, $key) {
                unset($item['player_id']);
                $item['total_points'] = ($item['3pt'] * 3) + ($item['2pt'] * 2) + $item['free_throws'];
                $item['field_goals_pct'] = $item['field_goals_attempted'] ? (round($item['field_goals'] / $item['field_goals_attempted'], 2) * 100) . '%' : 0;
                $item['3pt_pct'] = $item['3pt_attempted'] ? (round($item['3pt'] / $item['3pt_attempted'], 2) * 100) . '%' : 0;
                $item['2pt_pct'] = $item['2pt_attempted'] ? (round($item['2pt'] / $item['2pt_attempted'], 2) * 100) . '%' : 0;
                $item['free_throws_pct'] = $item['free_throws_attempted'] ? (round($item['free_throws'] / $item['free_throws_attempted'], 2) * 100) . '%' : 0;
                $item['total_rebounds'] = $item['offensive_rebounds'] + $item['defensive_rebounds'];
                return $item;
            });
    }

    function getPlayers($search) {

        $sqlwhere = $this->myFunction($search);

        $sql = "SELECT roster.*FROM roster WHERE $sqlwhere";
        return collect(query($sql))
            ->map(function($item, $key) {
                unset($item['id']);
                return $item;
            });
    }
    public function format($data, $format = 'html') {
        // return the right data format
        switch($format) {
            case 'xml':
                header('Content-type: text/xml');
                
                // fix any keys starting with numbers
                $keyMap = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
                $xmlData = [];
                foreach ($data->all() as $row) {
                    $xmlRow = [];
                    foreach ($row as $key => $value) {
                        $key = preg_replace_callback('(\d)', function($matches) use ($keyMap) {
                            return $keyMap[$matches[0]] . '_';
                        }, $key);
                        $xmlRow[$key] = $value;
                    }
                    $xmlData[] = $xmlRow;
                }
                $xml = Array2XML::createXML('data', [
                    'entry' => $xmlData
                ]);
                return $xml->saveXML();
                break;
            case 'json':
                header('Content-type: application/json');
                return json_encode($data->all());
                break;
            case 'csv':
                header('Content-type: text/csv');
                header('Content-Disposition: attachment; filename="export.csv";');
                if (!$data->count()) {
                    return;
                }
                $csv = [];
                
                // extract headings
                // replace underscores with space & ucfirst each word for a decent headings
                $headings = collect($data->get(0))->keys();
                $headings = $headings->map(function($item, $key) {
                    return collect(explode('_', $item))
                        ->map(function($item, $key) {
                            return ucfirst($item);
                        })
                        ->join(' ');
                });
                $csv[] = $headings->join(',');

                // format data
                foreach ($data as $dataRow) {
                    $csv[] = implode(',', array_values($dataRow));
                }
                return implode("\n", $csv);
                break;
            default: // html
                if (!$data->count()) {
                    return $this->htmlTemplate('Sorry, no matching data was found');
                }
                
                // extract headings
                // replace underscores with space & ucfirst each word for a decent heading
                $headings = collect($data->get(0))->keys();
                $headings = $headings->map(function($item, $key) {
                    return collect(explode('_', $item))
                        ->map(function($item, $key) {
                            return ucfirst($item);
                        })
                        ->join(' ');
                });
                $headings = '<tr><th>' . $headings->join('</th><th>') . '</th></tr>';

                // output data
                $rows = [];
                foreach ($data as $dataRow) {
                    $row = '<tr>';
                    foreach ($dataRow as $key => $value) {
                        $row .= '<td>' . $value . '</td>';
                    }
                    $row .= '</tr>';
                    $rows[] = $row;
                }
                $rows = implode('', $rows);
                return $this->htmlTemplate('<table>' . $headings . $rows . '</table>');
                break;
        }
    }

    // wrap html in a standard template
    public function htmlTemplate($html) {
        return '
<html>
<head>
<style type="text/css">
    body {
        font: 16px Roboto, Arial, Helvetica, Sans-serif;
    }
    td, th {
        padding: 4px 8px;
    }
    th {
        background: #eee;
        font-weight: 500;
    }
    tr:nth-child(odd) {
        background: #f4f4f4;
    }
</style>
</head>
<body>
    ' . $html . '
</body>
</html>';
    }
}

?>