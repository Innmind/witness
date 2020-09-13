---
currentMenu: philosophy
---

# Philosophy

## Why ?

The [Actor Model](https://en.wikipedia.org/wiki/Actor_model) is intended for highly concurrent applications handling a large volume of data. This model allows for highly available and resilient systems (with self healing mechanisms). A popular number for this model is Ericsson reaching an availability of [`99.999999999`%](https://en.wikipedia.org/wiki/Erlang_(programming_language)#History) in one of its products in 1998, that's nine nines meaning a downtime of about *31 milliseconds* per year. This level of availability was supposed to be unreachable.

Famous examples of products built on top of this model are WhatsApp (via [Erlang](https://www.erlang.org)) and Halo 4 Multiplayer backend (via [Orleans](https://dotnet.github.io/orleans/)).

To be able to provide a high concurrency the language/framework must allow a high number of actors, the goal is to divide the work as mush as the user needs to. All tools ([Erlang](https://www.erlang.org), [Ponylang](https://www.ponylang.io), [Akka](https://akka.io)) leverage threading under the hood to lower the cost of creating an actor.

All that being said, PHP seems to be an ill fitted language to use such programming model as it doesn't provide some advanced mechanism such as threading. Indeed PHP is not the perfect for this task since it was not built for this king of things (unlike Erlang). But it has advantages compared to other tools like Akka.

PHP is a share nothing language, each process is independent which is a core principle of the Actor Model to allow concurrency. This ability combined with the fact that the language is interpreted means that you can deploy your code, start a new process and you can have 2 processes of an application running 2 differents versions of the code; you don't have to shutdown all your application to release a new version. Another good point for availability.

PHP 8 introduces a JIT compiler, opening the door for new possibilities for PHP applications including long lived processes. Actors being long lived processes could benefit from this new feature.

The last advantage is a strategic one for companies already using PHP. Applications requirements evolve over time and current programming model may reach their limits pushing teams to search for new ways to solve their problems. Allowing PHP developers to experiment with the Actor Model allows companies to evolve at a lower cost and lower risk. Companies wouldn't have to either train their teams to a new language or hire new developers. In the case the Actor Model would reach its limit with PHP, the team would have gained enough experience to move the application to a more fitted language.

PHP applications realm as greatly increased over the years, it could evolve once more.

## Development process

Building such a tool will take quite some time before reaching a stable release, but in order to provide enough value along the way the development will follow these steps:

- make it work
- make it simple
- make it fast
