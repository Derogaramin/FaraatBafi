<?php
/**
 * کنترلر حاکمیت — جلسات، مصوبات، رأی‌گیری، مکاتبات داخلی (فاز ۳)
 */

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\InternalLetter;
use App\Modules\Admin\Models\Meeting;
use App\Modules\Admin\Models\Resolution;
use App\Modules\Admin\Models\Vote;
use App\Modules\Admin\Models\VoteBallot;
use Core\Auth;
use Core\CodeGenerator;
use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Session;

final class GovernanceController extends Controller
{
    public function meetings(Request $request): Response
    {
        $this->authorizeCan('governance.view');
        return $this->view('Admin::governance.meetings', [
            'title'    => 'جلسات هیئت‌مدیره',
            'meetings' => Meeting::builder()->orderBy('meeting_date', 'DESC')->get(),
        ]);
    }

    public function storeMeeting(Request $request): Response
    {
        $this->authorizeCan('governance.manage');
        $data = $this->validate($request->all(), [
            'title'        => 'required|min:3|max:200',
            'meeting_date' => 'required|jalali_date',
            'location'     => 'max:160',
            'agenda'       => 'max:2000',
        ], ['title' => 'عنوان جلسه', 'meeting_date' => 'تاریخ جلسه']);

        [$jy, $jm, $jd] = array_map('intval', preg_split('#[/\-.]#', en_digits((string) $data['meeting_date'])));
        $meeting = Meeting::create([
            'code'         => CodeGenerator::meetingCode($jy),
            'title'        => $data['title'],
            'meeting_date' => Jalali::jalaliDateToSql($jy, $jm, $jd),
            'location'     => $data['location'] ?? null,
            'agenda'       => $data['agenda'] ?? null,
            'status'       => 'scheduled',
            'created_by'   => Auth::id(),
        ]);
        Session::flash('success', 'جلسهٔ «' . $meeting->code . '» ثبت شد.');
        return $this->redirect(url('admin/governance/meetings/' . $meeting->id()));
    }

    public function meetingShow(Request $request): Response
    {
        $this->authorizeCan('governance.view');
        $meeting = Meeting::find($request->int('id'));
        if ($meeting === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Admin::governance.meeting-detail', [
            'title'       => 'جلسهٔ ' . $meeting->code,
            'meeting'     => $meeting->attributes(),
            'resolutions' => Resolution::builder()->where('meeting_id', (int) $meeting->id())->orderBy('sequence')->get(),
        ]);
    }

    public function saveMinutes(Request $request): Response
    {
        $this->authorizeCan('governance.manage');
        $meeting = Meeting::find($request->int('id'));
        if ($meeting === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'minutes' => 'required|min:10',
            'status'  => 'in:scheduled,held,cancelled',
        ], ['minutes' => 'صورت‌جلسه']);
        $meeting->update(['minutes' => $data['minutes'], 'status' => $data['status'] ?? 'held']);
        Session::flash('success', 'صورت‌جلسه ذخیره شد.');
        return $this->redirect(url('admin/governance/meetings/' . $meeting->id()));
    }

    public function addResolution(Request $request): Response
    {
        $this->authorizeCan('governance.manage');
        $meeting = Meeting::find($request->int('id'));
        if ($meeting === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'title' => 'required|min:3|max:200',
            'body'  => 'max:5000',
        ], ['title' => 'عنوان مصوبه']);

        [$jy, $jm] = Jalali::toJalali(
            (int) date('Y', strtotime((string) $meeting->meeting_date)),
            (int) date('n', strtotime((string) $meeting->meeting_date)),
            (int) date('j', strtotime((string) $meeting->meeting_date))
        );
        $sequence = (int) (Database::selectOne('SELECT COUNT(*) AS c FROM resolutions WHERE meeting_id = ?', [$meeting->id()])['c'] ?? 0) + 1;
        $code = sprintf('RES-%d-%02d-%02d', $jy, (int) substr((string) $meeting->code, -2), $sequence);

        Resolution::create([
            'code'       => $code,
            'meeting_id' => (int) $meeting->id(),
            'sequence'   => $sequence,
            'title'      => $data['title'],
            'body'       => $data['body'] ?? null,
            'status'     => 'approved',
        ]);
        Session::flash('success', 'مصوبهٔ «' . $code . '» ثبت شد.');
        return $this->redirect(url('admin/governance/meetings/' . $meeting->id()));
    }

    public function votes(Request $request): Response
    {
        $this->authorizeCan('governance.view');
        $votes = Vote::builder()->orderBy('id', 'DESC')->get();
        // شمارش آرا
        $tallies = [];
        foreach ($votes as $vote) {
            $ballots = Database::select(
                'SELECT choice, COUNT(*) AS c FROM vote_ballots WHERE vote_id = ? GROUP BY choice',
                [$vote->id()]
            );
            $tallies[$vote->id()] = $ballots;
        }
        return $this->view('Admin::governance.votes', [
            'title'   => 'رأی‌گیری‌ها',
            'votes'   => $votes,
            'tallies' => $tallies,
        ]);
    }

    public function storeVote(Request $request): Response
    {
        $this->authorizeCan('governance.manage');
        $data = $this->validate($request->all(), [
            'title'    => 'required|min:5|max:200',
            'options'  => 'required|min:3',
            'ends_at'  => 'required|jalali_date',
        ], ['title' => 'عنوان رأی‌گیری', 'options' => 'گزینه‌ها', 'ends_at' => 'پایان رأی‌گیری']);

        $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $data['options']))));
        if (count($options) < 2) {
            Session::flash('error', 'حداقل دو گزینه (هرکدام در یک خط) وارد کنید.');
            return $this->redirect(url('admin/governance/votes'));
        }
        [$jy, $jm, $jd] = array_map('intval', preg_split('#[/\-.]#', en_digits((string) $data['ends_at'])));
        Vote::create([
            'code'        => CodeGenerator::voteCode($jy),
            'title'       => $data['title'],
            'description' => $request->str('description') ?: null,
            'options'     => json_encode($options, JSON_UNESCAPED_UNICODE),
            'starts_at'   => date('Y-m-d H:i:s'),
            'ends_at'     => Jalali::jalaliDateToSql($jy, $jm, $jd) . ' 23:59:59',
            'status'      => 'open',
        ]);
        Session::flash('success', 'رأی‌گیری ایجاد و باز شد.');
        return $this->redirect(url('admin/governance/votes'));
    }

    public function castBallot(Request $request): Response
    {
        $this->authorizeCan('voting.participate');
        $vote = Vote::find($request->int('id'));
        if ($vote === null || $vote->status !== 'open') {
            Session::flash('error', 'این رأی‌گیری فعال نیست.');
            return $this->redirect(url('admin/governance/votes'));
        }
        if (strtotime((string) $vote->ends_at) < time()) {
            Session::flash('error', 'مهلت رأی‌گیری به پایان رسیده است.');
            return $this->redirect(url('admin/governance/votes'));
        }
        $exists = Database::selectOne('SELECT id FROM vote_ballots WHERE vote_id = ? AND user_id = ?', [$vote->id(), Auth::id()]);
        if ($exists) {
            Session::flash('warning', 'شما پیش‌تر در این رأی‌گیری شرکت کرده‌اید.');
            return $this->redirect(url('admin/governance/votes'));
        }
        VoteBallot::create([
            'vote_id'  => (int) $vote->id(),
            'user_id'  => (int) Auth::id(),
            'choice'   => $request->int('choice'),
            'voted_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'رأی شما ثبت شد. سپاس از مشارکت!');
        return $this->redirect(url('admin/governance/votes'));
    }

    public function letters(Request $request): Response
    {
        $this->authorizeCan('governance.view');
        return $this->view('Admin::governance.letters', [
            'title'   => 'مکاتبات داخلی',
            'letters' => Database::select(
                'SELECT il.*, uf.name AS from_name, ut.name AS to_name
                 FROM internal_letters il
                 LEFT JOIN users uf ON uf.id = il.from_user_id
                 LEFT JOIN users ut ON ut.id = il.to_user_id
                 ORDER BY il.id DESC LIMIT 100'
            ),
            'users'   => Database::select("SELECT id, name, code FROM users WHERE status='active' ORDER BY code"),
        ]);
    }

    public function storeLetter(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'to_user_id' => 'required|integer|exists:users,id',
            'subject'    => 'required|min:3|max:200',
            'body'       => 'required|min:5',
            'kind'       => 'in:letter,memo,notice',
        ], ['to_user_id' => 'گیرنده', 'subject' => 'موضوع', 'body' => 'متن']);

        $letter = InternalLetter::create([
            'code'         => CodeGenerator::letterCode(Jalali::currentYear()),
            'from_user_id' => Auth::id(),
            'to_user_id'   => (int) $data['to_user_id'],
            'subject'      => $data['subject'],
            'body'         => $data['body'],
            'kind'         => $data['kind'] ?? 'letter',
            'sent_at'      => date('Y-m-d H:i:s'),
        ]);
        \App\Modules\CRM\Models\Notification::push(
            (int) $data['to_user_id'],
            'نامهٔ داخلی: ' . $data['subject'],
            mb_substr((string) $data['body'], 0, 120) . '…',
            url('admin/governance/letters'),
            'info'
        );
        Session::flash('success', 'نامهٔ «' . $letter->code . '» ارسال شد.');
        return $this->redirect(url('admin/governance/letters'));
    }
}
