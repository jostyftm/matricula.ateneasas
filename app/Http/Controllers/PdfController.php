<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

use App\Student;
use App\Group;
use App\Grade;
use App\Schoolyear;
use App\Institution;
use App\Subgroup;

use PDF;
use Auth;

use setasign\Fpdi\Fpdi;

// 
use App\Pdf\Sheet\StudentAttendance;
use App\Pdf\Constancy\Study as ConstancyStudy;
use App\Pdf\Sheet\EvaluationSheet;


class PdfController extends Controller
{

    private function merge($path, $fileName = 'merge' ,$orientation='p')
    {
        $pdi = new Fpdi();

        $dir = opendir($path);
        $files = array();
        while ($archivo = readdir($dir)) {
                
            if (!is_dir($archivo)){
                // echo $archivo."<br />";
                array_push($files, $archivo);
            }
        }

        asort($files);
        
        foreach ($files as $file) 
        { 
            $pageCount = $pdi->setSourceFile($path.'/'.$file); 

            for ($i=1; $i <= $pageCount; $i++) { 
                
                $tpl = $pdi->importPage($i);
                $pdi->addPage($orientation); 

                $pdi->useTemplate($tpl); 
            }
        }

        //ob_clean();
        $pdi->Output('D',$fileName.'.pdf');

        system('rm -rf ' . escapeshellarg($path), $retval);
    }

    public function attendance($group_id, $year)
    {
        // 
        $group = Group::findOrFail($group_id);
        $institution = $group->headquarter->institution;
        $schoolYear = Schoolyear::where('year','=',$year)->first();

        $students = $group->enrollments()
        ->with('student')
        ->with('student.state')
        ->where('school_year_id', '=', $schoolYear->id)
        ->get()
        ->pluck('student')
        ->sortBy('last_name');

        // 
        $path = "./pdf/".time()."-".$group_id.$year."/";

        if(!file_exists($path))
        {   
            mkdir($path);
        }

        $attendance = new StudentAttendance('l', 'mm', 'letter');
        $attendance->institution = $institution;
        $attendance->group = $group;
        $attendance->create($students);
        $attendance->Output($path.$group_id.".pdf", "F");

        $this->merge($path, 'lista de asistencia - '.$group->name, 'l');
    }

    public function attendances(Request $request)
    {
        // 
        $path = "./pdf/".time()."-".$request->institution_id.$request->year."-attendance/";

        if(!file_exists($path))
        {   
            mkdir($path);
        }

        foreach($request->groups as $key => $group_id)
        {
            $group = Group::findOrFail($group_id);
            $institution = $group->headquarter->institution;
            $schoolYear = Schoolyear::where('year','=',$request->year)->first();

            // dd(count($institution->headquarters));
            $students = $group->enrollments()
            ->with('student')
            ->with('student.state')
            ->where('school_year_id', '=', $schoolYear->id)
            ->get()
            ->pluck('student')
            ->sortBy('last_name');

            $attendance = new StudentAttendance('l', 'mm', 'letter');
            $attendance->institution = $institution;
            $attendance->group = $group;
            $attendance->create($students);
            $attendance->Output($path.$group_id.".pdf", "F");

        }
        $this->merge($path, 'lista de asistencia_'.time()."_".$request->institution_id, 'l');
    }

    public function evaluationPdf(Request $request)
    {
        $path = "./pdf/".time()."-".$request->institution_id.$request->year."-evaluationSheet/";
        $institution = Institution::findOrFail($request->institution_id);
        $schoolYear = Schoolyear::where('year','=',$request->year)->first();
        $parameters = $institution->evaluationParameters()
                        ->with('criterias')
                        ->with('schoolYear')
                        ->where([
                            ['school_year_id', '=', '1'],
                            ['group_type', '=', $request->group_type]
                        ])
                        ->get();

        // return response()->json($parameters);

        if(!file_exists($path))
        {   
            mkdir($path);
        }

        foreach($request->groups as $key => $group_id)
        {
            $group_type = ($request->group_type == 'group') ? Group::findOrFail($group_id) : Subgroup::findOrFail($group_id) ;

            // dd(count($institution->headquarters));
            $students = $group_type->enrollments()
            ->with('student')
            ->with('student.state')
            ->where('school_year_id', '=', $schoolYear->id)
            ->get()
            ->pluck('student')
            ->sortBy('last_name');

            for ($i=0; $i < $request->copy; $i++) { 

                $evaluationSheet = new EvaluationSheet($request->orientation, 'mm', $request->papper);
                $evaluationSheet->institution = $institution;
                $evaluationSheet->group = $group_type;
                $evaluationSheet->parameters = $parameters;
                $evaluationSheet->create($students);
                $evaluationSheet->Output($path.$group_id.$i.".pdf", "F");
                
            }

        }

        $this->merge($path, 'Planilla-Evaluacion'.time()."_".$request->institution_id, $request->orientation);        
    }

    public function constancyStudy(Request $request)
    {
        $path = "./pdf/".time()."-".$request->institution_id.$request->year."-constancyStudy/";
        $institution = Institution::findOrFail($request->institution_id);
        $constancy = $institution->constancies()->where('type_id', '=', 1)->first();
        
        $constancy_Study = new ConstancyStudy($request->orientation, 'mm', $request->papper);
        $constancy_Study->constancy = $constancy;
        $constancy_Study->institution = $institution;

        if(!file_exists($path))
        {   
            mkdir($path);
        }

        if(isset($request->group_id_cs))
        {
            $group = Group::findOrFail($request->group_id_cs);

            if(!isset($request->students) && $group != null){
                $constancy_Study->createByGroup($group);
            }
            else
            {
                $constancy_Study->createByStudents($request->students);
            }
        }
        else
        {   
            $grade = Grade::findOrFail($request->grade_id_cs);

            $constancy_Study->createByGrade($grade);
        }

        $constancy_Study->Output($path.$institution->id."constancia.pdf", "F");
        
        $this->merge($path, 'Constancias', $request->orientation);
    }
}
