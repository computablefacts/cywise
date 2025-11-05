<?php

namespace Tests\Unit\WithDb;

use App\Models\Invitation;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseWithDbAndSeeders;

class InvitationTest extends TestCaseWithDbAndSeeders
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Disable tests with DB.');
    }
    protected function tearDown(): void
    {
        // $this->markTestSkipped('Disable tests with DB.');
    }


    #[Test]
    public function it_generates_an_invitation_with_token_and_sender()
    {
        $sender = User::factory()->create();
        $email = 'invitee@example.com';

        $invitation = Invitation::generate($email, $sender);

        $this->assertInstanceOf(Invitation::class, $invitation);
        $this->assertEquals($email, $invitation->email);
        $this->assertNotEmpty($invitation->token);
        $this->assertEquals($sender->id, $invitation->sent_by);
        $this->assertNotNull($invitation->expires_at);
        $this->assertFalse($invitation->isExpired());
    }

    #[Test]
    public function it_can_be_expired()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $invitation = Invitation::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($invitation->isExpired());
    }

    #[Test]
    public function it_cannot_have_null_sender()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $this->expectException(\TypeError::class);

        $email = 'invitee@example.com';
        Invitation::generate($email, null);
    }

    #[Test]
    public function sender_should_be_persistent()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $sender = User::factory()->create();
        $email = 'invitee@example.com';

        $invitation = Invitation::generate($email, $sender);

        // Reload from DB to check if changes are persisted
        $freshInvitation = Invitation::find($invitation->id);

        $this->assertNotNull($freshInvitation->sent_by);
    }

    #[Test]
    public function it_returns_true_when_invitation_is_already_used()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $invitation = Invitation::factory()->create([
            'accepted_at' => now(),
        ]);

        $this->assertTrue($invitation->alreadyUsed());
    }

    #[Test]
    public function it_returns_false_when_invitation_is_not_used()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $invitation = Invitation::factory()->create([
            'accepted_at' => null,
        ]);

        $this->assertFalse($invitation->alreadyUsed());
    }

    #[Test]
    public function it_can_be_accepted()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $invitation = Invitation::factory()->create([
            'accepted_at' => null,
            'received_by' => null,
        ]);
        $recipient = User::factory()->create();

        $invitation->acceptedBy($recipient);

        $this->assertEquals($recipient->id, $invitation->received_by);
        $this->assertNotNull($invitation->accepted_at);
    }

    #[Test]
    public function accepted_by_should_be_persistent()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $invitation = Invitation::factory()->create([
            'accepted_at' => null,
            'received_by' => null,
        ]);
        $recipient = User::factory()->create();

        $invitation->acceptedBy($recipient);

        // Reload from DB to check if changes are persisted
        $freshInvitation = Invitation::find($invitation->id);

        $this->assertNotNull($freshInvitation->accepted_at);
        $this->assertNotNull($freshInvitation->received_by);
    }

    #[Test]
    public function when_accepted_new_user_should_belong_to_the_same_tenant_than_sender()
    {
        $this->markTestSkipped('Disable tests with DB.');

        $sender = User::factory()->create();
        $email = 'invitee@example.com';
        $invitation = Invitation::generate($email, $sender);
        $recipient = User::factory()->create();

        $invitation->acceptedBy($recipient);

        $this->assertEquals($sender->tenant_id, $recipient->tenant_id);

        // Reload users from DB
        $freshSender = User::find($sender->id);
        $freshRecipient = User::find($recipient->id);

        $this->assertEquals($freshSender->tenant_id, $freshRecipient->tenant_id);
    }
}