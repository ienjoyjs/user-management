<?php

declare(strict_types=1);

namespace xenialdan\UserManager\commands\party;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use xenialdan\UserManager\API;
use xenialdan\UserManager\models\Party;
use xenialdan\UserManager\User;
use xenialdan\UserManager\UserStore;

class PartyInviteCommand extends BaseSubCommand
{

    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("usermanager.party.invite");
        $this->registerArgument(0, new RawStringArgument("Player", true));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param BaseArgument[] $args
     * @throws InvalidArgumentException
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $user = UserStore::getUser($sender);
        if ($user === null) {
            $sender->sendMessage("DEBUG: null");
            return;
        }
        $party = Party::getParty($user);
        if (!$party instanceof Party) {
            $user->getPlayer()->sendMessage(TextFormat::RED . "You are in no party");
            return;
        }
        if ($party->getOwnerId() !== $user->getId()) {
            $user->getPlayer()->sendMessage(TextFormat::RED . "You are not the owner of this party");
            return;
        }
        if (!isset($args["Player"])) {
            API::openUserSearchUI($sender, "Party Invite - User",
                function ($player, $user, $form) use ($party): void {
                    self::invite($party, $user);
                });
            return;
        }
        $name = trim($args["Player"] ?? "");
        if (empty($name)) {
            $sender->sendMessage("Invalid name given");
            return;
        }
        if (($friend = (UserStore::getUserByName($name))) instanceof User && $friend->getUsername() !== $sender->getLowerCaseName()) {
            self::invite($party, $user);
        } else {
            API::openUserNotFoundUI($sender, $name);
        }
    }

    /**
     * @param Party $party
     * @param User $user
     */
    private static function invite(Party $party, User $user): void
    {
        if ($party->isInvited($user)) {
            $party->getOwner()->getPlayer()->sendMessage(TextFormat::RED . $user->getDisplayName() . " already has been invited to the party!");
            return;
        }
        $party->inviteMember($user);
        $user->getPlayer()->sendMessage(TextFormat::GOLD . "You have been invited to the party \"{$party->getName()}\" of " . $party->getOwner()->getDisplayName());
        $party->getOwner()->getPlayer()->sendMessage(TextFormat::GREEN . $user->getDisplayName() . " has been invited to the party");
    }
}
