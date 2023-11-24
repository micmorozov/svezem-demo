#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 20 - Перевозка 1 тонна

Numeral -> "1";
NumeralWord -> "один";
Number -> Numeral | NumeralWord;

Tonn -> "т"|"тн"|"тон"|"тонна"|"тонный"|"тонник";

Phrase -> Number Tonn;

//Частный случай
//Т.к часто пишут 1т без пробелов
SpecialCase -> AnyWord<wff=/1-?(т|тн|тон(н|ны|ный|ник)?)/>;

Fact -> Phrase | SpecialCase | "однотонный";

S -> Fact interp(Category.Category_20);

