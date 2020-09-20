sig Machine {
	actors: disj set Actor,
	supervisor: disj one Supervisor,
}
sig Address {
}
sig Mailbox {
	address: disj one Address,
	messages: disj set Message,
}
sig Actor {
	mailbox: disj one Mailbox,
	produce: disj set Message,
	children: disj set Actor,
	parent: one Actor,
} {
	parent not in children
}
one sig RootActor extends Actor {} { no parent }
sig Message {
}
sig Signal {
}
sig Supervisor {
	actors: disj set Actor,
}

fact { all a: Actor | a.children.parent = a }
fact { all m: Message | m in Mailbox.messages => m not in Actor.produce }
fact { all m: Message | m in Actor.produce => m not in Mailbox.messages }

assert actorOnOneMachine {
	all a: Actor, m1, m2: Machine | m1 != m2 and a in m1.actors => a not in m2.actors
}
assert mailboxIsNotSharedBetweenActors {
	all a1, a2: Actor | a1 != a2 => a1.mailbox not in a2.mailbox
}
assert anActorIsSupervisedByOnlyOneActor {
	all a1, a2: Actor | a1 != a2 => a1.children not in a2.children
}
assert actorsFormATree {
	no a: Actor | a in a.^children
}
assert messagesAreNotShared {
	all m: Message | m in Mailbox.messages => m not in Actor.produce
	all m: Message | m in Actor.produce => m not in Mailbox.messages
	all m: Message, m1, m2: Mailbox | m1 != m2 and m in m1.messages => m not in m2.messages
	all m: Message, a1, a2: Actor | a1 != a2 and m in a1.produce => m not in a2.produce
}
assert actorSupervisedByOneSupervisor {
	all a: Actor, s1, s2: Supervisor | s1 != s2 and a in s1.actors => a not in s2.actors
}
assert mailboxAdressesAreUnique {
	all m1, m2: Mailbox | m1 != m2 => m1.address not in m2.address
}

check actorOnOneMachine
check mailboxIsNotSharedBetweenActors
check anActorIsSupervisedByOnlyOneActor
check actorsFormATree
check messagesAreNotShared
check actorSupervisedByOneSupervisor
check mailboxAdressesAreUnique
