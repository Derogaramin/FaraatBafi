<?php
/**
 * کنترلر تیکت‌ها در پنل مدیر
 */

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Modules\CRM\Models\Ticket;
use App\Modules\CRM\Models\TicketReply;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Events;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class AdminTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->str('status');
        $where = '1=1';
        $bindings = [];
        if ($status !== '') {
            $where = 't.status = ?';
            $bindings[] = $status;
        }
        $tickets = Database::select(
            "SELECT t.*, u.name AS user_name, u.code AS user_code,
                    (SELECT COUNT(*) FROM ticket_replies tr WHERE tr.ticket_id = t.id) AS replies_count
             FROM tickets t JOIN users u ON u.id = t.user_id
             WHERE {$where} ORDER BY FIELD(t.status,'open','answered','closed'), t.id DESC LIMIT 200",
            $bindings
        );
        return $this->view('CRM::admin.index', [
            'title'    => 'تیکت‌های پشتیبانی',
            'tickets'  => $tickets,
            'statuses' => Statuses::TICKET,
            'filter'   => $status,
        ]);
    }

    public function show(Request $request): Response
    {
        $ticket = Ticket::find($request->int('id'));
        if ($ticket === null) {
            throw new \Core\HttpException(404);
        }
        $user = Database::selectOne('SELECT name, code, phone, email FROM users WHERE id = ?', [$ticket->user_id]);
        return $this->view('CRM::admin.detail', [
            'title'   => 'تیکت ' . $ticket->code,
            'ticket'  => $ticket->attributes(),
            'user'    => $user,
            'replies' => Database::select(
                'SELECT tr.*, u.name, u.code AS user_code FROM ticket_replies tr
                 JOIN users u ON u.id = tr.user_id WHERE tr.ticket_id = ? ORDER BY tr.id',
                [$ticket->id()]
            ),
            'mine'    => false,
        ]);
    }

    public function reply(Request $request): Response
    {
        $ticket = Ticket::find($request->int('id'));
        if ($ticket === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'body' => 'required|min:2|max:5000',
        ], ['body' => 'متن پاسخ']);

        TicketReply::create([
            'ticket_id'  => (int) $ticket->id(),
            'user_id'    => (int) Auth::id(),
            'body'       => $data['body'],
            'is_staff'   => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $ticket->update(['status' => 'answered', 'updated_at' => date('Y-m-d H:i:s')]);
        Events::dispatch('ticket.replied', ['ticket' => $ticket, 'by_staff' => true]);

        Session::flash('success', 'پاسخ ثبت و برای کاربر اعلان ارسال شد.');
        return $this->redirect(url('admin/tickets/' . $ticket->id()));
    }

    public function changeStatus(Request $request): Response
    {
        $ticket = Ticket::find($request->int('id'));
        if ($ticket === null) {
            throw new \Core\HttpException(404);
        }
        $status = $request->str('status');
        if (!array_key_exists($status, Statuses::TICKET)) {
            Session::flash('error', 'وضعیت نامعتبر است.');
            return $this->redirect(url('admin/tickets/' . $ticket->id()));
        }
        $ticket->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        Session::flash('success', 'وضعیت تیکت به «' . Statuses::label('ticket', $status) . '» تغییر کرد.');
        return $this->redirect(url('admin/tickets/' . $ticket->id()));
    }
}
