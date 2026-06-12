<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class DatabaseBackupController extends Controller
{
    public function index()
    {
        return Inertia::render('System/BackupDatabase');
    }

    public function export()
    {
        // Simple SQL Export logic
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $property = "Tables_in_{$dbName}";

        $sql = '-- SQL Dump Generated at '.now()."\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$property;
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0]->{'Create Table'};
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n{$createTable};\n\n";

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(fn ($v) => is_null($v) ? 'NULL' : "'".addslashes((string) $v)."'", (array) $row);
                $sql .= "INSERT INTO `{$tableName}` VALUES (".implode(', ', $values).");\n";
            }
            $sql .= "\n\n";
        }

        $filename = 'backup-'.date('Y-m-d-His').'.sql';

        return Response::make($sql, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $sql = file_get_contents($request->file('file')->getRealPath());

        DB::unprepared($sql);

        return back()->with('success', 'Database imported successfully.');
    }
}
