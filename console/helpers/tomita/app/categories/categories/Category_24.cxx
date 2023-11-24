#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 23 - Перевозка 10 тонн

Numeral -> AnyWord<wff=/20([\s-]?(и|ти))?/>;
NumeralWord -> "двадцать";
Number -> Numeral | NumeralWord;

Tonn -> "т"|"тн"|"тон"|"тонна"|"тонный"|"тонник";

Phrase -> Number Tonn;
OneWord -> "двадцатитонный" | "двадцатитонник";

//Частный случай
//Т.к часто пишут 20т без пробелов
SpecialCase -> AnyWord<wff=/20-?(т|тн|тон(н|ны|ный|ник)?)/>;

Fact -> Phrase | SpecialCase | OneWord;

S -> Fact interp(Category.Category_24);
