#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 23 - Перевозка 10 тонн

Numeral -> AnyWord<wff=/10([\s-]?(и|ти))?/>;
NumeralWord -> "десять";
Number -> Numeral | NumeralWord;

Tonn -> "т"|"тн"|"тон"|"тонна"|"тонный"|"тонник";

Phrase -> Number Tonn;
OneWord -> "десятитонный" | "десятитонник";

//Частный случай
//Т.к часто пишут 10т без пробелов
SpecialCase -> AnyWord<wff=/10-?(т|тн|тон(н|ны|ный|ник)?)/>;

Fact -> Phrase | SpecialCase | OneWord;

S -> Fact interp(Category.Category_23);
